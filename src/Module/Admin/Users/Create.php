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

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Admin\BaseUsers;

class Create extends BaseUsers
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/users/create', 'admin_users_create');

		$nu_name     = $_POST['new_user_name']     ?? '';
		$nu_nickname = $_POST['new_user_nickname'] ?? '';
		$nu_email    = $_POST['new_user_email']    ?? '';
		$nu_language = DI::config()->get('system', 'language');

		if ($nu_name !== '' && $nu_email !== '' && $nu_nickname !== '') {
			try {
				User::createMinimal($nu_name, $nu_email, $nu_nickname, $nu_language);
				DI::baseUrl()->redirect('admin/users');
			} catch (\Exception $ex) {
				notice($ex->getMessage());
			}
		}

		DI::baseUrl()->redirect('admin/users/create');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$t = Renderer::getMarkupTemplate('admin/users/create.tpl');
		return self::getTabsHTML('all') . Renderer::replaceMacros($t, [
			// strings //
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('New User'),
			'$submit' => DI::l10n()->t('Add User'),

			'$form_security_token' => self::getFormSecurityToken('admin_users_create'),

			// values //
			'$baseurl' => DI::baseUrl()->get(true),
			'$query_string' => DI::args()->getQueryString(),

			'$newusername' => ['new_user_name', DI::l10n()->t('Name'), '', DI::l10n()->t('Name of the new user.')],
			'$newusernickname' => ['new_user_nickname', DI::l10n()->t('Nickname'), '', DI::l10n()->t('Nickname of the new user.')],
			'$newuseremail' => ['new_user_email', DI::l10n()->t('Email'), '', DI::l10n()->t('Email address of the new user.'), '', '', 'email'],
		]);
	}
}
