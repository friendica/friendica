<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ServiceUnavailableException;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;

abstract class BaseUsers extends BaseModeration
{
	/** @var Database */
	protected $database;

	public function __construct(Database $database, Page $page, AppHelper $appHelper, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $appHelper, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	/**
	 * Get the users moderation tabs menu
	 *
	 * @param string $selectedTab
	 * @return string HTML
	 * @throws ServiceUnavailableException
	 */
	protected function getTabsHTML(string $selectedTab): string
	{
		$all     = $this->database->count('user', ["`uid` != ?", 0]);
		$active  = $this->database->count('user', ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `uid` != ?", 0]);
		$pending = Register::getPendingCount();
		$blocked = $this->database->count('user', ['blocked' => true, 'verified' => true, 'account_removed' => false]);
		$deleted = $this->database->count('user', ['account_removed' => true]);

		$tabs = [
			[
				'label'     => $this->t('All') . ' (' . $all . ')',
				'url'       => 'moderation/users',
				'sel'       => !$selectedTab || $selectedTab == 'all' ? 'active' : '',
				'title'     => $this->t('List of all users'),
				'id'        => 'admin-users-all',
				'accesskey' => 'a',
			],
			[
				'label'     => $this->t('Active') . ' (' . $active . ')',
				'url'       => 'moderation/users/active',
				'sel'       => $selectedTab == 'active' ? 'active' : '',
				'title'     => $this->t('List of active accounts'),
				'id'        => 'admin-users-active',
				'accesskey' => 'k',
			],
			[
				'label'     => $this->t('Pending') . ($pending ? ' (' . $pending . ')' : ''),
				'url'       => 'moderation/users/pending',
				'sel'       => $selectedTab == 'pending' ? 'active' : '',
				'title'     => $this->t('List of pending registrations'),
				'id'        => 'admin-users-pending',
				'accesskey' => 'p',
			],
			[
				'label'     => $this->t('Blocked') . ($blocked ? ' (' . $blocked . ')' : ''),
				'url'       => 'moderation/users/blocked',
				'sel'       => $selectedTab == 'blocked' ? 'active' : '',
				'title'     => $this->t('List of blocked users'),
				'id'        => 'admin-users-blocked',
				'accesskey' => 'b',
			],
			[
				'label'     => $this->t('Deleted') . ($deleted ? ' (' . $deleted . ')' : ''),
				'url'       => 'moderation/users/deleted',
				'sel'       => $selectedTab == 'deleted' ? 'active' : '',
				'title'     => $this->t('List of pending user deletions'),
				'id'        => 'admin-users-deleted',
				'accesskey' => 'd',
			],
		];
		$tabs_arr = ['tabs' => $tabs, 'selectedTab' => $selectedTab];
		Hook::callAll('moderation_users_tabs', $tabs_arr);

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		return Renderer::replaceMacros($tpl, ['$tabs' => $tabs_arr['tabs'], '$more' => $this->t('More')]);
	}

	protected function setupUserCallback(): \Closure
	{
		$adminlist = User::getAdminEmailList();
		return function ($user) use ($adminlist) {
			$page_types = [
				User::PAGE_FLAGS_NORMAL    => $this->t('Normal Account Page'),
				User::PAGE_FLAGS_SOAPBOX   => $this->t('Soapbox Page'),
				User::PAGE_FLAGS_COMMUNITY => $this->t('Public Group'),
				User::PAGE_FLAGS_COMM_MAN  => $this->t('Public Group - Restricted'),
				User::PAGE_FLAGS_FREELOVE  => $this->t('Automatic Friend Page'),
				User::PAGE_FLAGS_PRVGROUP  => $this->t('Private Group')
			];
			$account_types = [
				User::ACCOUNT_TYPE_PERSON       => $this->t('Personal Page'),
				User::ACCOUNT_TYPE_ORGANISATION => $this->t('Organisation Page'),
				User::ACCOUNT_TYPE_NEWS         => $this->t('News Page'),
				User::ACCOUNT_TYPE_COMMUNITY    => $this->t('Community Group'),
				User::ACCOUNT_TYPE_RELAY        => $this->t('Relay'),
			];

			$user['page_flags_raw'] = $user['page-flags'];
			$user['page_flags']     = $page_types[$user['page-flags']];

			$user['account_type_raw'] = ($user['page_flags_raw'] == 0) ? $user['account-type'] : -1;
			$user['account_type']     = ($user['page_flags_raw'] == 0) ? $account_types[$user['account-type']] : '';

			$user['register_date'] = Temporal::getRelativeDate($user['register_date']);
			$user['login_date']    = Temporal::getRelativeDate($user['last-activity'], false);
			$user['lastitem_date'] = Temporal::getRelativeDate($user['last-item']);
			$user['is_admin']      = in_array($user['email'], $adminlist);
			$user['is_deletable']  = !$user['account_removed'] && intval($user['uid']) != $this->session->getLocalUserId();
			$user['deleted']       = $user['account_removed'] ? Temporal::getRelativeDate($user['account_expires_on']) : false;

			return $user;
		};
	}
}
