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

namespace Friendica\Module\Admin\Users;

use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Admin\BaseUsers;

class Active extends BaseUsers
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError(DI::baseUrl()->get(true), 'admin_users_active');

		$users = $_POST['user'] ?? [];

		if (!empty($_POST['page_users_block'])) {
			foreach ($users as $uid) {
				User::block($uid);
			}
			info(DI::l10n()->tt('%s user blocked', '%s users blocked', count($users)));
		}

		if (!empty($_POST['page_users_delete'])) {
			foreach ($users as $uid) {
				if (local_user() != $uid) {
					User::remove($uid);
				} else {
					notice(DI::l10n()->t('You can\'t remove yourself'));
				}
			}

			info(DI::l10n()->tt('%s user deleted', '%s users deleted', count($users)));
		}

		DI::baseUrl()->redirect(DI::args()->getQueryString());
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$action = $this->parameters['action'] ?? '';
		$uid = $this->parameters['uid'] ?? 0;

		if ($uid) {
			$user = User::getById($uid, ['username', 'blocked']);
			if (!DBA::isResult($user)) {
				notice(DI::l10n()->t('User not found'));
				DI::baseUrl()->redirect('admin/users');
				return ''; // NOTREACHED
			}
		}

		switch ($action) {
			case 'delete':
				if (local_user() != $uid) {
					self::checkFormSecurityTokenRedirectOnError('admin/users/active', 'admin_users_active', 't');
					// delete user
					User::remove($uid);

					notice(DI::l10n()->t('User "%s" deleted', $user['username']));
				} else {
					notice(DI::l10n()->t('You can\'t remove yourself'));
				}

				DI::baseUrl()->redirect('admin/users/active');
				break;
			case 'block':
				self::checkFormSecurityTokenRedirectOnError('admin/users/active', 'admin_users_active', 't');
				User::block($uid);
				notice(DI::l10n()->t('User "%s" blocked', $user['username']));
				DI::baseUrl()->redirect('admin/users/active');
				break;
		}
		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 100);

		$valid_orders = [
			'name',
			'email',
			'register_date',
			'login_date',
			'last-item',
			'page-flags'
		];

		$order = 'name';
		$order_direction = '+';
		if (!empty($_GET['o'])) {
			$new_order = $_GET['o'];
			if ($new_order[0] === '-') {
				$order_direction = '-';
				$new_order = substr($new_order, 1);
			}

			if (in_array($new_order, $valid_orders)) {
				$order = $new_order;
			}
		}

		$users = User::getList($pager->getStart(), $pager->getItemsPerPage(), 'active', $order, ($order_direction == '-'));

		$users = array_map(self::setupUserCallback(), $users);

		$th_users = array_map(null, [DI::l10n()->t('Name'), DI::l10n()->t('Email'), DI::l10n()->t('Register date'), DI::l10n()->t('Last login'), DI::l10n()->t('Last public item'), DI::l10n()->t('Type')], $valid_orders);

		$count = DBA::count('user', ["NOT `blocked` AND `verified` AND NOT `account_removed` AND `uid` != ?", 0]);

		$t = Renderer::getMarkupTemplate('admin/users/active.tpl');
		return self::getTabsHTML('active') .	Renderer::replaceMacros($t, [
			// strings //
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Active Accounts'),
			'$select_all' => DI::l10n()->t('select all'),
			'$delete' => DI::l10n()->t('Delete'),
			'$block' => DI::l10n()->t('Block'),
			'$blocked' => DI::l10n()->t('User blocked'),
			'$siteadmin' => DI::l10n()->t('Site admin'),
			'$accountexpired' => DI::l10n()->t('Account expired'),
			'$h_newuser' => DI::l10n()->t('Create a new user'),

			'$th_users' => $th_users,
			'$order_users' => $order,
			'$order_direction_users' => $order_direction,

			'$confirm_delete_multi' => DI::l10n()->t('Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'),
			'$confirm_delete' => DI::l10n()->t('The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'),

			'$form_security_token' => self::getFormSecurityToken('admin_users_active'),

			// values //
			'$baseurl' => DI::baseUrl()->get(true),
			'$query_string' => DI::args()->getQueryString(),

			'$users' => $users,
			'$count' => $count,
			'$pager' => $pager->renderFull($count),
		]);
	}
}
