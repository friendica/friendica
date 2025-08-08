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

		# Keep these in sync with the descriptions in Module/Register.php
		$nickdesc1 = $this->t('Nicknames must start with a letter, and may contain latin letters, numbers and underscores');
		$nickdesc2 = $this->t('Your profile address on this site will then be "<strong>nickname@%s</strong>".', DI::baseUrl()->getHost());

		$t = Renderer::getMarkupTemplate('moderation/users/create.tpl');
		return self::getTabsHTML('all') . Renderer::replaceMacros($t, [
			// strings //
			'$title'       => $this->t('Moderation'),
			'$page'        => $this->t('Create user'),
			'$description' => $this->t('Type in the details for the new user to be created.'),
			'$submit'      => $this->t('Create user'),

			'$form_security_token' => self::getFormSecurityToken('admin_users_create'),

			// values //
			'$query_string' => $this->args->getQueryString(),

			'$newusername'     => ['new_user_name', '', '', '', true, 'autofocus', '', $this->t('Display name')],
			'$newusernickname' => ['new_user_nickname', '', '', $nickdesc1 . "<br>" . $nickdesc2, true, '', '', $this->t('Nickname')],
			'$newuseremail'    => ['new_user_email', '', '', '', true, 'email', '', $this->t('Email')],
		]);
	}
}
