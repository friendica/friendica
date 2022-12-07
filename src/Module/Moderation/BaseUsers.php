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

namespace Friendica\Module\Moderation;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\DI;
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

	public function __construct(Database $database, App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

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
		$active  = $this->database->count('user', ["NOT `blocked` AND `verified` AND NOT `account_removed` AND `uid` != ?", 0]);
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

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		return Renderer::replaceMacros($tpl, ['$tabs' => $tabs]);
	}

	protected function setupUserCallback(): \Closure
	{
		$adminlist = User::getAdminEmailList();
		return function ($user) use ($adminlist) {
			$page_types = [
				User::PAGE_FLAGS_NORMAL    => $this->t('Normal Account Page'),
				User::PAGE_FLAGS_SOAPBOX   => $this->t('Soapbox Page'),
				User::PAGE_FLAGS_COMMUNITY => $this->t('Public Forum'),
				User::PAGE_FLAGS_FREELOVE  => $this->t('Automatic Friend Page'),
				User::PAGE_FLAGS_PRVGROUP  => $this->t('Private Forum')
			];
			$account_types = [
				User::ACCOUNT_TYPE_PERSON       => $this->t('Personal Page'),
				User::ACCOUNT_TYPE_ORGANISATION => $this->t('Organisation Page'),
				User::ACCOUNT_TYPE_NEWS         => $this->t('News Page'),
				User::ACCOUNT_TYPE_COMMUNITY    => $this->t('Community Forum'),
				User::ACCOUNT_TYPE_RELAY        => $this->t('Relay'),
			];

			$user['page_flags_raw'] = $user['page-flags'];
			$user['page_flags']     = $page_types[$user['page-flags']];

			$user['account_type_raw'] = ($user['page_flags_raw'] == 0) ? $user['account-type'] : -1;
			$user['account_type']     = ($user['page_flags_raw'] == 0) ? $account_types[$user['account-type']] : '';

			$user['register_date'] = Temporal::getRelativeDate($user['register_date']);
			$user['login_date']    = Temporal::getRelativeDate($user['last-activity']);
			$user['lastitem_date'] = Temporal::getRelativeDate($user['last-item']);
			$user['is_admin']      = in_array($user['email'], $adminlist);
			$user['is_deletable']  = !$user['account_removed'] && intval($user['uid']) != $this->session->getLocalUserId();
			$user['deleted']       = $user['account_removed'] ? Temporal::getRelativeDate($user['account_expires_on']) : false;

			return $user;
		};
	}
}
