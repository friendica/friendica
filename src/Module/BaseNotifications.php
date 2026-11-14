<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Navigation\Notifications\ValueObject\FormattedNotify;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Base Module for each tab of the notification display
 *
 * General possibility to print it as JSON as well
 */
abstract class BaseNotifications extends BaseModule
{
	/** @var array Array of URL parameters */
	public const URL_TYPES = [
		FormattedNotify::NETWORK  => 'network',
		FormattedNotify::SYSTEM   => 'system',
		FormattedNotify::HOME     => 'home',
		FormattedNotify::PERSONAL => 'personal',
		FormattedNotify::INTRO    => 'intros',
	];

	/** @var array Array of the allowed notifications and their printable name */
	public const PRINT_TYPES = [
		FormattedNotify::NETWORK  => 'Network',
		FormattedNotify::SYSTEM   => 'System',
		FormattedNotify::HOME     => 'Home',
		FormattedNotify::PERSONAL => 'Personal',
		FormattedNotify::INTRO    => 'Introductions',
	];

	/** @var array The array of access keys for notification pages */
	public const ACCESS_KEYS = [
		FormattedNotify::NETWORK  => 'w',
		FormattedNotify::SYSTEM   => 'y',
		FormattedNotify::HOME     => 'h',
		FormattedNotify::PERSONAL => 'r',
		FormattedNotify::INTRO    => 'i',
	];

	/** @var int The default count of items per page */
	public const ITEMS_PER_PAGE = 20;
	/** @var int The default limit of notifications per page */
	public const DEFAULT_PAGE_LIMIT = 80;

	/** @var boolean True, if ALL entries should get shown */
	protected $showAll;
	/** @var int The determined start item of the current page */
	protected $firstItemNum;

	/** @var Arguments */
	protected $args;

	/**
	 * Collects all notifications from the backend
	 *
	 * @return array The determined notification array
	 *               ['header', 'notifications']
	 */
	abstract public function getNotifications();

	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		if (!$session->getLocalUserId()) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		$page = ($_REQUEST['page'] ?? 0) ?: 1;

		$this->firstItemNum = ($page * self::ITEMS_PER_PAGE) - self::ITEMS_PER_PAGE;
		$this->showAll      = ($_REQUEST['show'] ?? '') === 'all';
	}

	protected function rawContent(array $request = [])
	{
		// If the last argument of the query is NOT json, return
		if ($this->args->get($this->args->getArgc() - 1) !== 'json') {
			return;
		}

		// Set the pager
		$pager = new Pager($this->l10n, $this->args->getQueryString(), self::ITEMS_PER_PAGE);

		// Add additional informations (needed for json output)
		$notifications = [
			'notifications' => $this->getNotifications(),
			'items_page'    => $pager->getItemsPerPage(),
			'page'          => $pager->getPage(),
		];

		$this->earlyJsonExit($notifications);
	}

	/**
	 * Shows the printable result of notifications for a specific tab
	 *
	 * @param string $header        The notification header
	 * @param array  $notifications The array with the notifications
	 * @param string $noContent     The string in case there are no notifications
	 * @param array  $showLink      The possible links at the top
	 *
	 * @return string The rendered output
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function printContent(string $header, array $notifications, string $noContent, array $showLink)
	{
		// Get the nav tabs for the notification pages
		$tabs = $this->getTabs();

		// Set the pager
		$pager = new Pager($this->l10n, $this->args->getQueryString(), self::ITEMS_PER_PAGE);

		$notif_tpl = Renderer::getMarkupTemplate('notifications/notifications.tpl');
		return Renderer::replaceMacros($notif_tpl, [
			'$header'        => $header ?: $this->t('Notifications'),
			'$tabs'          => $tabs,
			'$notifications' => $notifications,
			'$noContent'     => $noContent,
			'$showLink'      => $showLink,
			'$paginate'      => $pager->renderMinimal(count($notifications)),
		]);
	}

	/**
	 * List of pages for the Notifications TabBar
	 *
	 * @param L10n   $l10n     The translation class
	 * @param string $selected The URL part of the active tab
	 *
	 * @return array with notifications TabBar data
	 */
	public static function getTabBar(L10n $l10n, string $selected): array
	{
		$tabs = [[
			'label' => $l10n->t('Stream'),
			'url'   => 'notifications/stream',
			'sel'   => ($selected == 'stream') ? 'active' : '',
			'id'    => 'stream-tab',
		]];

		foreach (self::URL_TYPES as $type => $url) {
			$tabs[] = [
				'label'     => $l10n->t(self::PRINT_TYPES[$type]),
				'url'       => 'notifications/' . $url . (($url == "personal") ? "?show=all" : ""),
				'sel'       => (($selected == $url) ? 'active' : ''),
				'id'        => $type . '-tab',
				'accesskey' => self::ACCESS_KEYS[$type],
			];
		}

		return $tabs;
	}

	private function getTabs(): array
	{
		return self::getTabBar($this->l10n, $this->args->get(1, ''));
	}
}
