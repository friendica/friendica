<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Security;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class PasswordTooLong extends \Friendica\BaseModule
{
	public function __construct(private readonly SystemMessages $sysmsg, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, private readonly IHandleUserSessions $userSession, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$newpass = $request['password'];
		$confirm = $request['password_confirm'];

		try {
			if ($newpass != $confirm) {
				throw new \Exception($this->l10n->t('Passwords do not match.'));
			}

			//  check if the old password was supplied correctly before changing it to the new value
			User::getIdFromPasswordAuthentication($this->userSession->getLocalUserId(), $request['password_current']);

			if (strlen($request['password_current']) <= 72) {
				throw new \Exception($this->l10n->t('Password does not need changing.'));
			}

			$result = User::updatePassword($this->userSession->getLocalUserId(), $newpass);
			if (!DBA::isResult($result)) {
				throw new \Exception($this->l10n->t('Password update failed. Please try again.'));
			}

			$this->sysmsg->addInfo($this->l10n->t('Password changed.'));

			$this->baseUrl->redirect($request['return_url'] ?? '');
		} catch (\Exception $e) {
			$this->sysmsg->addNotice($e->getMessage());
			$this->sysmsg->addNotice($this->l10n->t('Password unchanged.'));
		}
	}

	protected function content(array $request = []): string
	{
		/** @phpstan-ignore notIdentical.alwaysFalse(value of PASSWORD_DEFAULT will be change in a future PHP version) */
		if (PASSWORD_DEFAULT !== PASSWORD_BCRYPT) {
			// Nothing to do here
			$this->baseUrl->redirect();
		}

		$tpl = Renderer::getMarkupTemplate('security/password_too_long.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'ptitle' => $this->l10n->t('Password Too Long'),
				'desc'   => $this->l10n->t('Since version 2022.09, we\'ve realized that any password longer than 72 characters is truncated during hashing. To prevent any confusion about this behavior, please update your password to be fewer or equal to 72 characters.'),
				'submit' => $this->l10n->t('Update Password'),
			],

			'$form_security_token' => self::getFormSecurityToken('security/password_too_long'),
			'$return_url'          => $request['return_url'] ?? '',

			'$password_current' => ['password_current', $this->l10n->t('Current Password:'), '', $this->l10n->t('Your current password to confirm the changes'), 'required', 'autocomplete="off"'],
			'$password'         => ['password', $this->l10n->t('New Password:'), '', $this->l10n->t('Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.') . ' ' . $this->l10n->t('Password length is limited to 72 characters.'), 'required', 'autocomplete="off"', User::getPasswordRegExp()],
			'$password_confirm' => ['password_confirm', $this->l10n->t('Confirm:'), '', '', 'required', 'autocomplete="off"'],
		]);

		return $o;
	}
}
