<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Group;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Module\Conversation\Network;
use Friendica\Module\Register;
use Friendica\Module\Response;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Navigation\Notifications\Exception\NoMessageException;
use Friendica\Navigation\Notifications\Factory;
use Friendica\Navigation\Notifications\Repository;
use Friendica\Navigation\Notifications\ValueObject;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

class Ping extends BaseModule
{
	/** @var SystemMessages */
	private $systemMessages;
	/** @var Repository\Notification */
	private $notificationRepo;
	/** @var Introduction */
	private $introductionRepo;
	/** @var Factory\FormattedNavNotification */
	private $formattedNavNotification;
	/** @var IHandleUserSessions */
	private $session;
	/** @var IManageConfigValues */
	private $config;
	/** @var IManagePersonalConfigValues */
	private $pconfig;
	/** @var Database */
	private $database;
	/** @var ICanCache */
	private $cache;
	/** @var Repository\Notify */
	private $notify;
	/** @var App */
	private $app;

	public function __construct(App $app, Repository\Notify $notify, ICanCache $cache, Database $database, IManagePersonalConfigValues $pconfig, IManageConfigValues $config, IHandleUserSessions $session, SystemMessages $systemMessages, Repository\Notification $notificationRepo, Introduction $introductionRepo, Factory\FormattedNavNotification $formattedNavNotification, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->systemMessages           = $systemMessages;
		$this->notificationRepo         = $notificationRepo;
		$this->introductionRepo         = $introductionRepo;
		$this->formattedNavNotification = $formattedNavNotification;
		$this->session                  = $session;
		$this->config                   = $config;
		$this->pconfig                  = $pconfig;
		$this->database                 = $database;
		$this->cache                    = $cache;
		$this->notify                   = $notify;
		$this->app                      = $app;
	}

	protected function rawContent(array $request = [])
	{
		$registrations    = [];
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

		// Suppress notification display for forum accounts
		if ($this->session->getLocalUserId() && $this->session->get('page_flags', '') != User::PAGE_FLAGS_COMMUNITY) {
			if ($this->pconfig->get($this->session->getLocalUserId(), 'system', 'detailed_notif')) {
				$notifications = $this->notificationRepo->selectDetailedForUser($this->session->getLocalUserId());
			} else {
				$notifications = $this->notificationRepo->selectDigestForUser($this->session->getLocalUserId());
			}

			$condition = [
				"`unseen` AND `uid` = ? AND NOT `origin` AND (`vid` != ? OR `vid` IS NULL)",
				$this->session->getLocalUserId(), Verb::getID(Activity::FOLLOW)
			];

			// No point showing counts for non-top-level posts when the network page is ordered by received field
			if (Network::getTimelineOrderBySession($this->session, $this->pconfig) == 'received') {
				$condition = DBA::mergeConditions($condition, ["`parent` = `id`"]);
			}

			$items_unseen = $this->database->toArray(Post::selectForUser(
				$this->session->getLocalUserId(),
				['wall', 'uid', 'uri-id'],
				$condition,
				['limit' => 1000],
			));
			$arr = ['items' => $items_unseen];
			Hook::callAll('network_ping', $arr);

			foreach ($items_unseen as $item) {
				if ($item['wall']) {
					$home_count++;
				} else {
					$network_count++;
				}
			}

			$compute_group_counts = $this->config->get('system','compute_group_counts');
			if ($network_count && $compute_group_counts) {
				// Find out how unseen network posts are spread across groups
				foreach (Group::countUnseen() as $group_count) {
					if ($group_count['count'] > 0) {
						$groups_unseen[] = $group_count;
					}
				}

				foreach (ForumManager::countUnseenItems() as $forum_count) {
					if ($forum_count['count'] > 0) {
						$forums_unseen[] = $forum_count;
					}
				}
			}

			$intros = $this->introductionRepo->selectForUser($this->session->getLocalUserId());

			$intro_count = $intros->count();

			$myurl      = $this->session->getMyUrl();
			$mail_count = $this->database->count('mail', ["`uid` = ? AND NOT `seen` AND `from-url` != ?", $this->session->getLocalUserId(), $myurl]);

			if (intval($this->config->get('config', 'register_policy')) === Register::APPROVE && $this->app->isSiteAdmin()) {
				$registrations = \Friendica\Model\Register::getPending();
				$register_count = count($registrations);
			}

			$cachekey = 'ping:events:' . $this->session->getLocalUserId();
			$events   = $this->cache->get($cachekey);
			if (is_null($events)) {
				$events = $this->database->selectToArray('event', ['type', 'start'],
					["`uid` = ? AND `start` < ? AND `finish` > ? AND NOT `ignore`",
						$this->session->getLocalUserId(), DateTimeFormat::utc('now + 7 days'), DateTimeFormat::utcNow()]);
				$this->cache->set($cachekey, $events, Duration::HOUR);
			}

			$now_date = DateTimeFormat::localNow('Y-m-d');
			foreach ($events as $event) {
				$is_birthday = false;
				if ($event['type'] === 'birthday') {
					$birthday_count++;
					$is_birthday = true;
				} else {
					$event_count++;
				}

				if (DateTimeFormat::local($event['start'], 'Y-m-d') === $now_date) {
					if ($is_birthday) {
						$today_birthday_count++;
					} else {
						$today_event_count++;
					}
				}
			}

			$owner = User::getOwnerDataById($this->session->getLocalUserId());

			$navNotifications = array_map(function (Entity\Notification $notification) use ($owner) {
				if (!$this->notify->shouldShowOnDesktop($notification)) {
					return null;
				}
				if (($notification->type == Post\UserNotification::TYPE_NONE) && in_array($owner['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP])) {
					return null;
				}
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
				try {
					$navNotifications[] = $this->formattedNavNotification->createFromIntro($intro);
				} catch (HTTPException\NotFoundException $e) {
					$this->introductionRepo->delete($intro);
				}
			}

			if (count($registrations) <= 1 || $this->pconfig->get($this->session->getLocalUserId(), 'system', 'detailed_notif')) {
				foreach ($registrations as $registration) {
					$navNotifications[] = $this->formattedNavNotification->createFromParams(
						$registration['name'],
						$registration['url'],
						$this->l10n->t('{0} requested registration'),
						new \DateTime($registration['created'], new \DateTimeZone('UTC')),
						new Uri($this->baseUrl . '/moderation/users/pending')
					);
				}
			} else {
				$navNotifications[] = $this->formattedNavNotification->createFromParams(
					$registrations[0]['name'],
					$registrations[0]['url'],
					$this->l10n->t('{0} and %d others requested registration', count($registrations) - 1),
					new \DateTime($registrations[0]['created'], new \DateTimeZone('UTC')),
					new Uri($this->baseUrl . '/moderation/users/pending')
				);
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
			'notice' => $this->systemMessages->flushNotices(),
			'info'   => $this->systemMessages->flushInfos(),
		];

		if (isset($_GET['callback'])) {
			// JSONP support
			System::httpExit($_GET['callback'] . '(' . json_encode(['result' => $data]) . ')', Response::TYPE_BLANK, 'application/javascript');
		} else {
			System::jsonExit(['result' => $data]);
		}
	}
}
