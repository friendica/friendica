<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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

		$nu_name     = $request['new_user_name']     ?? '';
		$nu_nickname = $request['new_user_nickname'] ?? '';
		$nu_email    = $request['new_user_email']    ?? '';
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
