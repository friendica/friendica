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
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module\Admin\BaseUsers;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Temporal;

class Deleted extends BaseUsers
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/users/deleted', 'admin_users_deleted');

		// @TODO: Implement user deletion cancellation

		DI::baseUrl()->redirect('admin/users/deleted');
	}

	protected function content(array $request = []): string
	{
		parent::content();

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

		$users = User::getList($pager->getStart(), $pager->getItemsPerPage(), 'removed', $order, ($order_direction == '-'));

		$users = array_map(self::setupUserCallback(), $users);

		$count = DBA::count('user', ['account_removed' => true]);

		$t = Renderer::getMarkupTemplate('admin/users/deleted.tpl');
		return self::getTabsHTML('deleted') . Renderer::replaceMacros($t, [
			// strings //
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Users awaiting permanent deletion'),

			'$th_deleted' => [DI::l10n()->t('Name'), DI::l10n()->t('Email'), DI::l10n()->t('Register date'), DI::l10n()->t('Last login'), DI::l10n()->t('Last public item'), DI::l10n()->t('Permanent deletion')],

			'$form_security_token' => self::getFormSecurityToken('admin_users_deleted'),

			// values //
			'$baseurl' => DI::baseUrl()->get(true),
			'$query_string' => DI::args()->getQueryString(),

			'$users' => $users,
			'$count' => $count,
			'$pager' => $pager->renderFull($count),
		]);
	}
}
