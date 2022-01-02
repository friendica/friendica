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

class Pending extends BaseUsers
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/users/pending', 'admin_users_pending');

		$pending = $_POST['pending'] ?? [];

		if (!empty($_POST['page_users_approve'])) {
			foreach ($pending as $hash) {
				User::allow($hash);
			}
			info(DI::l10n()->tt('%s user approved', '%s users approved', count($pending)));
		}

		if (!empty($_POST['page_users_deny'])) {
			foreach ($pending as $hash) {
				User::deny($hash);
			}
			info(DI::l10n()->tt('%s registration revoked', '%s registrations revoked', count($pending)));
		}

		DI::baseUrl()->redirect('admin/users/pending');
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
			case 'allow':
				self::checkFormSecurityTokenRedirectOnError('/admin/users/pending', 'admin_users_pending', 't');
				User::allow(Register::getPendingForUser($uid)['hash'] ?? '');
				notice(DI::l10n()->t('Account approved.'));
				DI::baseUrl()->redirect('admin/users/pending');
				break;
			case 'deny':
				self::checkFormSecurityTokenRedirectOnError('/admin/users/pending', 'admin_users_pending', 't');
				User::deny(Register::getPendingForUser($uid)['hash'] ?? '');
				notice(DI::l10n()->t('Registration revoked'));
				DI::baseUrl()->redirect('admin/users/pending');
				break;
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 100);

		$pending = Register::getPending($pager->getStart(), $pager->getItemsPerPage());

		$count = Register::getPendingCount();

		$t = Renderer::getMarkupTemplate('admin/users/pending.tpl');
		return self::getTabsHTML('pending') . Renderer::replaceMacros($t, [
			// strings //
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('User registrations awaiting review'),
			'$select_all' => DI::l10n()->t('select all'),
			'$th_pending' => [DI::l10n()->t('Request date'), DI::l10n()->t('Name'), DI::l10n()->t('Email')],
			'$no_pending' => DI::l10n()->t('No registrations.'),
			'$pendingnotetext' => DI::l10n()->t('Note from the user'),
			'$approve' => DI::l10n()->t('Approve'),
			'$deny' => DI::l10n()->t('Deny'),

			'$form_security_token' => self::getFormSecurityToken('admin_users_pending'),

			// values //
			'$baseurl' => DI::baseUrl()->get(true),
			'$query_string' => DI::args()->getQueryString(),

			'$pending' => $pending,
			'$count' => $count,
			'$pager' => $pager->renderFull($count),
		]);
	}
}
