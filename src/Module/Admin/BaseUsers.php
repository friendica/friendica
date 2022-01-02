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

namespace Friendica\Module\Admin;

use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Temporal;

abstract class BaseUsers extends BaseAdmin
{
	/**
	 * Get the users admin tabs menu
	 *
	 * @param string $selectedTab
	 * @return string HTML
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected static function getTabsHTML(string $selectedTab)
	{
		$all     = DBA::count('user', ["`uid` != ?", 0]);
		$active  = DBA::count('user', ["NOT `blocked` AND `verified` AND NOT `account_removed` AND `uid` != ?", 0]);
		$pending = Register::getPendingCount();
		$blocked = DBA::count('user', ['blocked' => true, 'verified' => true, 'account_removed' => false]);
		$deleted = DBA::count('user', ['account_removed' => true]);

		$tabs = [
			[
				'label'	=> DI::l10n()->t('All') . ' (' . $all . ')',
				'url'	=> 'admin/users',
				'sel'	=> !$selectedTab || $selectedTab == 'all' ? 'active' : '',
				'title'	=> DI::l10n()->t('List of all users'),
				'id'	=> 'admin-users-all',
				'accesskey' => 'a',
			],
			[
				'label'	=> DI::l10n()->t('Active') . ' (' . $active . ')',
				'url'	=> 'admin/users/active',
				'sel'	=> $selectedTab == 'active' ? 'active' : '',
				'title'	=> DI::l10n()->t('List of active accounts'),
				'id'	=> 'admin-users-active',
				'accesskey' => 'k',
			],
			[
				'label'	=> DI::l10n()->t('Pending') . ($pending ? ' (' . $pending . ')' : ''),
				'url'	=> 'admin/users/pending',
				'sel'	=> $selectedTab == 'pending' ? 'active' : '',
				'title'	=> DI::l10n()->t('List of pending registrations'),
				'id'	=> 'admin-users-pending',
				'accesskey' => 'p',
			],
			[
				'label'	=> DI::l10n()->t('Blocked') . ($blocked ? ' (' . $blocked . ')' : ''),
				'url'	=> 'admin/users/blocked',
				'sel'	=> $selectedTab == 'blocked' ? 'active' : '',
				'title'	=> DI::l10n()->t('List of blocked users'),
				'id'	=> 'admin-users-blocked',
				'accesskey' => 'b',
			],
			[
				'label'	=> DI::l10n()->t('Deleted') . ($deleted ? ' (' . $deleted . ')' : ''),
				'url'	=> 'admin/users/deleted',
				'sel'	=> $selectedTab == 'deleted' ? 'active' : '',
				'title'	=> DI::l10n()->t('List of pending user deletions'),
				'id'	=> 'admin-users-deleted',
				'accesskey' => 'd',
			],
		];

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		return Renderer::replaceMacros($tpl, ['$tabs' => $tabs]);
	}

	protected static function setupUserCallback() {
		$adminlist = explode(',', str_replace(' ', '', DI::config()->get('config', 'admin_email')));
		return function ($user) use ($adminlist) {
			$page_types = [
				User::PAGE_FLAGS_NORMAL    => DI::l10n()->t('Normal Account Page'),
				User::PAGE_FLAGS_SOAPBOX   => DI::l10n()->t('Soapbox Page'),
				User::PAGE_FLAGS_COMMUNITY => DI::l10n()->t('Public Forum'),
				User::PAGE_FLAGS_FREELOVE  => DI::l10n()->t('Automatic Friend Page'),
				User::PAGE_FLAGS_PRVGROUP  => DI::l10n()->t('Private Forum')
			];
			$account_types = [
				User::ACCOUNT_TYPE_PERSON       => DI::l10n()->t('Personal Page'),
				User::ACCOUNT_TYPE_ORGANISATION => DI::l10n()->t('Organisation Page'),
				User::ACCOUNT_TYPE_NEWS         => DI::l10n()->t('News Page'),
				User::ACCOUNT_TYPE_COMMUNITY    => DI::l10n()->t('Community Forum'),
				User::ACCOUNT_TYPE_RELAY        => DI::l10n()->t('Relay'),
			];

			$user['page_flags_raw'] = $user['page-flags'];
			$user['page_flags'] = $page_types[$user['page-flags']];

			$user['account_type_raw'] = ($user['page_flags_raw'] == 0) ? $user['account-type'] : -1;
			$user['account_type'] = ($user['page_flags_raw'] == 0) ? $account_types[$user['account-type']] : '';

			$user['register_date'] = Temporal::getRelativeDate($user['register_date']);
			$user['login_date'] = Temporal::getRelativeDate($user['login_date']);
			$user['lastitem_date'] = Temporal::getRelativeDate($user['last-item']);
			$user['is_admin'] = in_array($user['email'], $adminlist);
			$user['is_deletable'] = !$user['account_removed'] && intval($user['uid']) != local_user();
			$user['deleted'] = ($user['account_removed'] ? Temporal::getRelativeDate($user['account_expires_on']) : False);

			return $user;
		};
	}
}
