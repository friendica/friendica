<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Moderation\Users;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Moderation\BaseUsers;

class Create extends BaseUsers
{
	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		self::checkFormSecurityTokenRedirectOnError('moderation/users/create', 'admin_users_create');

		$nu_name     = $request['new_user_name'] ?? '';
		$nu_nickname = $request['new_user_nickname'] ?? '';
		$nu_email    = $request['new_user_email'] ?? '';
		$nu_language = DI::config()->get('system', 'language');

		if ($nu_name !== '' && $nu_email !== '' && $nu_nickname !== '') {
			try {
				User::createMinimal($nu_name, $nu_email, $nu_nickname, $nu_language);
				$this->baseUrl->redirect('moderation/users');
			} catch (\Exception $ex) {
				$this->systemMessages->addNotice($ex->getMessage());
			}
		}

		$this->baseUrl->redirect('moderation/users/create');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$t = Renderer::getMarkupTemplate('moderation/users/create.tpl');
		return self::getTabsHTML('all') . Renderer::replaceMacros($t, [
			// strings //
			'$title'  => $this->t('Administration'),
			'$page'   => $this->t('New User'),
			'$submit' => $this->t('Add User'),

			'$form_security_token' => self::getFormSecurityToken('admin_users_create'),

			// values //
			'$query_string' => $this->args->getQueryString(),

			'$newusername'     => ['new_user_name', $this->t('Name'), '', $this->t('Name of the new user.')],
			'$newusernickname' => ['new_user_nickname', $this->t('Nickname'), '', $this->t('Nickname of the new user.')],
			'$newuseremail'    => ['new_user_email', $this->t('Email'), '', $this->t('Email address of the new user.'), '', '', 'email'],
		]);
	}
}
