<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Notifications;

use Friendica\App;
use Friendica\App\Arguments;
use Friendica\Content\Nav;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\BaseNotifications;
use Friendica\Module\Response;
use Friendica\Navigation\Notifications\ValueObject\FormattedNotify;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Prints all notification types except introduction:
 * - Network
 * - System
 * - Personal
 * - Home
 */
class Notifications extends BaseNotifications
{
	/** @var \Friendica\Navigation\Notifications\Factory\FormattedNotify */
	protected $formattedNotifyFactory;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, \Friendica\Navigation\Notifications\Factory\FormattedNotify $formattedNotifyFactory, IHandleUserSessions $userSession, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $userSession, $server, $parameters);

		$this->formattedNotifyFactory = $formattedNotifyFactory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getNotifications()
	{
		$notificationHeader = '';
		$notifications      = [];

		$factory = $this->formattedNotifyFactory;

		if (($this->args->get(1) == 'network')) {
			$notificationHeader = $this->t('Network Notifications');
			$notifications      = [
				'ident'         => FormattedNotify::NETWORK,
				'notifications' => $factory->getNetworkList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif (($this->args->get(1) == 'system')) {
			$notificationHeader = $this->t('System Notifications');
			$notifications      = [
				'ident'         => FormattedNotify::SYSTEM,
				'notifications' => $factory->getSystemList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif (($this->args->get(1) == 'personal')) {
			$notificationHeader = $this->t('Personal Notifications');
			$notifications      = [
				'ident'         => FormattedNotify::PERSONAL,
				'notifications' => $factory->getPersonalList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif (($this->args->get(1) == 'home')) {
			$notificationHeader = $this->t('Home Notifications');
			$notifications      = [
				'ident'         => FormattedNotify::HOME,
				'notifications' => $factory->getHomeList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} else {
			$this->baseUrl->redirect('notifications');
		}

		return [
			'header'        => $notificationHeader,
			'notifications' => $notifications,
		];
	}

	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		Nav::setSelected('notifications');

		$notificationContent   = [];
		$notificationNoContent = '';

		$notificationResult = $this->getNotifications();
		$notifications      = $notificationResult['notifications'] ?? [];
		$notificationHeader = $notificationResult['header']        ?? '';

		if (!empty($notifications['notifications'])) {
			$notificationTemplates = [
				'like'         => 'notifications/likes_item.tpl',
				'dislike'      => 'notifications/dislikes_item.tpl',
				'attend'       => 'notifications/attend_item.tpl',
				'attendno'     => 'notifications/attend_item.tpl',
				'attendmaybe'  => 'notifications/attend_item.tpl',
				'friend'       => 'notifications/friends_item.tpl',
				'comment'      => 'notifications/comments_item.tpl',
				'post'         => 'notifications/posts_item.tpl',
				'notification' => 'notifications/notification.tpl',
			];
			// Loop trough ever notification This creates an array with the output html for each
			// notification and apply the correct template according to the notificationtype (label).
			/** @var FormattedNotify $Notification */
			foreach ($notifications['notifications'] as $Notification) {
				$notificationArray = $Notification->toArray();

				$notificationTemplate = Renderer::getMarkupTemplate($notificationTemplates[$notificationArray['label']]);

				$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
					'$notification' => $notificationArray
				]);
			}
		} else {
			$notificationNoContent = $this->t('No more %s notifications.', $notificationResult['ident']);
		}

		$notificationShowLink = [];
		if ($notifications['ident'] != "personal") {
			$notificationShowLink = [
				'href' => ($this->showAll ? 'notifications/' . $notifications['ident'] : 'notifications/' . $notifications['ident'] . '?show=all'),
				'text' => ($this->showAll ? $this->t('Show unread') : $this->t('Show all')),
			];
		}

		return $this->printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
