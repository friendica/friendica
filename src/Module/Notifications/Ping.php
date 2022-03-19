<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Notifications;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Contact\Introduction\Repository\Introduction;
use Friendica\Content\ForumManager;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Group;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Module\Register;
use Friendica\Module\Response;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Navigation\Notifications\Exception\NoMessageException;
use Friendica\Navigation\Notifications\Factory;
use Friendica\Navigation\Notifications\Repository;
use Friendica\Navigation\Notifications\ValueObject;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

class Ping extends BaseModule
{
	/** @var Repository\Notification */
	private $notificationRepo;
	/** @var Introduction */
	private $introductionRepo;
	/** @var Factory\FormattedNavNotification */
	private $formattedNavNotification;

	public function __construct(Repository\Notification $notificationRepo, Introduction $introductionRepo, Factory\FormattedNavNotification $formattedNavNotification, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->notificationRepo         = $notificationRepo;
		$this->introductionRepo         = $introductionRepo;
		$this->formattedNavNotification = $formattedNavNotification;
	}

	protected function rawContent(array $request = [])
	{
		$regs             = [];
		$navNotifications = [];

		$intro_count     = 0;
		$mail_count      = 0;
		$home_count      = 0;
		$network_count   = 0;
		$register_count  = 0;
		$sysnotify_count = 0;
		$groups_unseen   = [];
		$forums_unseen   = [];

		$event_count          = 0;
		$today_event_count    = 0;
		$birthday_count       = 0;
		$today_birthday_count = 0;


		if (local_user()) {
			if (DI::pConfig()->get(local_user(), 'system', 'detailed_notif')) {
				$notifications = $this->notificationRepo->selectDetailedForUser(local_user());
			} else {
				$notifications = $this->notificationRepo->selectDigestForUser(local_user());
			}

			$condition = [
				"`unseen` AND `uid` = ? AND NOT `origin` AND (`vid` != ? OR `vid` IS NULL)",
				local_user(), Verb::getID(Activity::FOLLOW)
			];
			$items = Post::selectForUser(local_user(), ['wall', 'uid', 'uri-id'], $condition, ['limit' => 1000]);
			if (DBA::isResult($items)) {
				$items_unseen = Post::toArray($items, false);
				$arr          = ['items' => $items_unseen];
				Hook::callAll('network_ping', $arr);

				foreach ($items_unseen as $item) {
					if ($item['wall']) {
						$home_count++;
					} else {
						$network_count++;
					}
				}
			}
			DBA::close($items);

			if ($network_count) {
				// Find out how unseen network posts are spread across groups
				$group_counts = Group::countUnseen();
				if (DBA::isResult($group_counts)) {
					foreach ($group_counts as $group_count) {
						if ($group_count['count'] > 0) {
							$groups_unseen[] = $group_count;
						}
					}
				}

				$forum_counts = ForumManager::countUnseenItems();
				if (DBA::isResult($forum_counts)) {
					foreach ($forum_counts as $forum_count) {
						if ($forum_count['count'] > 0) {
							$forums_unseen[] = $forum_count;
						}
					}
				}
			}

			$intros = $this->introductionRepo->selectForUser(local_user());

			$intro_count = $intros->count();

			$myurl      = DI::baseUrl() . '/profile/' . DI::app()->getLoggedInUserNickname();
			$mail_count = DBA::count('mail', ["`uid` = ? AND NOT `seen` AND `from-url` != ?", local_user(), $myurl]);

			if (intval(DI::config()->get('config', 'register_policy')) === Register::APPROVE && DI::app()->isSiteAdmin()) {
				$regs = \Friendica\Model\Register::getPending();

				if (DBA::isResult($regs)) {
					$register_count = count($regs);
				}
			}

			$cachekey = 'ping:events:' . local_user();
			$ev       = DI::cache()->get($cachekey);
			if (is_null($ev)) {
				$ev = DBA::selectToArray('event', ['type', 'start'],
					["`uid` = ? AND `start` < ? AND `finish` > ? AND NOT `ignore`",
						local_user(), DateTimeFormat::utc('now + 7 days'), DateTimeFormat::utcNow()]);
				if (DBA::isResult($ev)) {
					DI::cache()->set($cachekey, $ev, Duration::HOUR);
				}
			}

			if (DBA::isResult($ev)) {
				$all_events = count($ev);

				if ($all_events) {
					$str_now = DateTimeFormat::localNow('Y-m-d');
					foreach ($ev as $x) {
						$bd = false;
						if ($x['type'] === 'birthday') {
							$birthday_count++;
							$bd = true;
						} else {
							$event_count++;
						}
						if (DateTimeFormat::local($x['start'], 'Y-m-d') === $str_now) {
							if ($bd) {
								$today_birthday_count++;
							} else {
								$today_event_count++;
							}
						}
					}
				}
			}

			$navNotifications = array_map(function (Entity\Notification $notification) {
				try {
					return $this->formattedNavNotification->createFromNotification($notification);
				} catch (NoMessageException $e) {
					return null;
				}
			}, $notifications->getArrayCopy());
			$navNotifications = array_filter($navNotifications);

			$sysnotify_count = array_reduce($navNotifications, function (int $carry, ValueObject\FormattedNavNotification $navNotification) {
				return $carry + ($navNotification->seen ? 0 : 1);
			}, 0);

			// merge all notification types in one array
			foreach ($intros as $intro) {
				$navNotifications[] = $this->formattedNavNotification->createFromIntro($intro);
			}

			if (DBA::isResult($regs)) {
				if (count($regs) <= 1 || DI::pConfig()->get(local_user(), 'system', 'detailed_notif')) {
					foreach ($regs as $reg) {
						$navNotifications[] = $this->formattedNavNotification->createFromParams(
							[
								'name' => $reg['name'],
								'url'  => $reg['url'],
							],
							DI::l10n()->t('{0} requested registration'),
							new \DateTime($reg['created'], new \DateTimeZone('UTC')),
							new Uri(DI::baseUrl()->get(true) . '/admin/users/pending')
						);
					}
				} else {
					$navNotifications[] = $this->formattedNavNotification->createFromParams(
						[
							'name' => $regs[0]['name'],
							'url'  => $regs[0]['url'],
						],
						DI::l10n()->t('{0} and %d others requested registration', count($regs) - 1),
						new \DateTime($regs[0]['created'], new \DateTimeZone('UTC')),
						new Uri(DI::baseUrl()->get(true) . '/admin/users/pending')
					);
				}
			}

			// sort notifications by $[]['date']
			$sort_function = function (ValueObject\FormattedNavNotification $a, ValueObject\FormattedNavNotification $b) {
				$a = $a->toArray();
				$b = $b->toArray();

				// Unseen messages are kept at the top
				if ($a['seen'] == $b['seen']) {
					if ($a['timestamp'] == $b['timestamp']) {
						return 0;
					} else {
						return $a['timestamp'] < $b['timestamp'] ? 1 : -1;
					}
				} else {
					return $a['seen'] ? 1 : -1;
				}
			};
			usort($navNotifications, $sort_function);
		}

		$sysmsgs      = [];
		$sysmsgs_info = [];

		if (!empty($_SESSION['sysmsg'])) {
			$sysmsgs = $_SESSION['sysmsg'];
			unset($_SESSION['sysmsg']);
		}

		if (!empty($_SESSION['sysmsg_info'])) {
			$sysmsgs_info = $_SESSION['sysmsg_info'];
			unset($_SESSION['sysmsg_info']);
		}

		$notification_count = $sysnotify_count + $intro_count + $register_count;

		$data             = [];
		$data['intro']    = $intro_count;
		$data['mail']     = $mail_count;
		$data['net']      = ($network_count < 1000) ? $network_count : '999+';
		$data['home']     = ($home_count < 1000) ? $home_count : '999+';
		$data['register'] = $register_count;

		$data['events']          = $event_count;
		$data['events-today']    = $today_event_count;
		$data['birthdays']       = $birthday_count;
		$data['birthdays-today'] = $today_birthday_count;
		$data['groups']          = $groups_unseen;
		$data['forums']          = $forums_unseen;
		$data['notification']    = ($notification_count < 50) ? $notification_count : '49+';

		$data['notifications'] = $navNotifications;

		$data['sysmsgs'] = [
			'notice' => $sysmsgs,
			'info'   => $sysmsgs_info
		];

		if (isset($_GET['callback'])) {
			// JSONP support
			header("Content-type: application/javascript");
			echo $_GET['callback'] . '(' . json_encode(['result' => $data]) . ')';
			exit;
		} else {
			System::jsonExit(['result' => $data]);
		}
	}
}
