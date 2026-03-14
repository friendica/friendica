<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Notifications;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Contact\Introduction\Repository\Introduction;
use Friendica\Content\GroupManager;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Circle;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Model\Verb;
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
use Friendica\Util\Strings;
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
	/** @var AppHelper */
	private $appHelper;

	public function __construct(AppHelper $appHelper, Repository\Notify $notify, ICanCache $cache, Database $database, IManagePersonalConfigValues $pconfig, IManageConfigValues $config, IHandleUserSessions $session, SystemMessages $systemMessages, Repository\Notification $notificationRepo, Introduction $introductionRepo, Factory\FormattedNavNotification $formattedNavNotification, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
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
		$this->appHelper                = $appHelper;
	}

	protected function rawContent(array $request = [])
	{
		$registrations    = [];
		$navNotifications = [];

		$intro_count     = 0;
		$mail_count      = 0;
		$home_count      = 0;
		$register_count  = 0;
		$sysnotify_count = 0;
		$circles_unseen  = [];
		$groups_unseen   = [];

		$event_count          = 0;
		$today_event_count    = 0;
		$birthday_count       = 0;
		$today_birthday_count = 0;

		// Suppress notification display for group accounts
		if ($this->session->getLocalUserId() && $this->session->get('page_flags', '') != User::PAGE_FLAGS_COMMUNITY) {
			if ($this->pconfig->get($this->session->getLocalUserId(), 'system', 'detailed_notif')) {
				$notifications = $this->notificationRepo->selectDetailedForUser($this->session->getLocalUserId());
			} else {
				$notifications = $this->notificationRepo->selectDigestForUser($this->session->getLocalUserId());
			}

			$condition = [
				"`unseen` AND `uid` = ? AND NOT `origin` AND `wall` AND (`vid` != ? OR `vid` IS NULL)",
				$this->session->getLocalUserId(), Verb::getID(Activity::FOLLOW),
			];

			$home_count = Post::count($condition);

			if ($this->config->get('system', 'compute_circle_counts')) {
				// Find out how unseen network posts are spread across circles
				foreach (Circle::countUnseen($this->session->getLocalUserId()) as $circle_count) {
					if ($circle_count['count'] > 0) {
						$circles_unseen[] = $circle_count;
					}
				}

				foreach (GroupManager::countUnseenItems() as $group_count) {
					if ($group_count['count'] > 0) {
						$groups_unseen[] = $group_count;
					}
				}
			}

			$intros = $this->introductionRepo->selectForUser($this->session->getLocalUserId());

			$intro_count = $intros->count();

			$myurl      = $this->session->getMyUrl();
			$mail_count = $this->database->count('mail', ["`uid` = ? AND NOT `seen` AND `from-url` != ?", $this->session->getLocalUserId(), $myurl]);

			if (Register::getPolicy() === Register::APPROVE && $this->session->isSiteAdmin()) {
				$registrations  = \Friendica\Model\Register::getPending();
				$register_count = count($registrations);
			}

			$cachekey = 'ping:events:' . $this->session->getLocalUserId();
			$events   = $this->cache->get($cachekey);
			if (is_null($events)) {
				$events = $this->database->selectToArray(
					'event',
					['type', 'start'],
					["`uid` = ? AND `start` < ? AND `finish` > ? AND NOT `ignore`",
						$this->session->getLocalUserId(), DateTimeFormat::utc('now + 7 days'), DateTimeFormat::utcNow()],
				);
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
				if (($notification->type == Post\UserNotification::TYPE_NONE) && in_array($owner['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP, User::PAGE_FLAGS_COMM_MAN])) {
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
						new Uri($this->baseUrl . '/moderation/users/pending'),
					);
				}
			} else {
				$navNotifications[] = $this->formattedNavNotification->createFromParams(
					$registrations[0]['name'],
					$registrations[0]['url'],
					$this->l10n->t('{0} and %d others requested registration', count($registrations) - 1),
					new \DateTime($registrations[0]['created'], new \DateTimeZone('UTC')),
					new Uri($this->baseUrl . '/moderation/users/pending'),
				);
			}

			// sort notifications by $[]['date']
			$sort_function = function (ValueObject\FormattedNavNotification $a, ValueObject\FormattedNavNotification $b) {
				$a = $a->toArray();
				$b = $b->toArray();

				// Unseen messages are kept at the top
				if ($a['seen'] == $b['seen']) {
					return $b['timestamp'] <=> $a['timestamp'];
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
		$data['home']     = ($home_count < 1000) ? $home_count : '999+';
		$data['register'] = $register_count;

		$data['events']          = $event_count;
		$data['events-today']    = $today_event_count;
		$data['birthdays']       = $birthday_count;
		$data['birthdays-today'] = $today_birthday_count;
		$data['circles']         = $circles_unseen;
		$data['groups']          = $groups_unseen;
		$data['notification']    = ($notification_count < 50) ? $notification_count : '49+';

		$data['notifications'] = $navNotifications;

		$data['sysmsgs'] = [
			'notice' => array_map([Strings::class, 'escapeHtml'], $this->systemMessages->flushNotices()),
			'info'   => array_map([Strings::class, 'escapeHtml'], $this->systemMessages->flushInfos()),
		];

		if (isset($_GET['callback'])) {
			// JSONP support
			$this->httpExit($_GET['callback'] . '(' . json_encode(['result' => $data]) . ')', Response::TYPE_BLANK, 'application/javascript');
		} else {
			$this->jsonExit(['result' => $data]);
		}
	}
}
