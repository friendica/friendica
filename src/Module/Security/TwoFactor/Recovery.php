<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Security\TwoFactor;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Security\Authentication;
use Friendica\Security\TwoFactor\CodeVerifier;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * // Page 1a: Recovery code verification
 *
 * @package Friendica\Module\TwoFactor
 */
class Recovery extends BaseModule
{
	protected $errors = [];

	/** @var IHandleUserSessions */
	protected $session;
	/** @var AppHelper */
	protected $appHelper;
	/** @var Authentication */
	protected $auth;
	/** @var CodeVerifier */
	protected $codeVerifier;

	public function __construct(AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Authentication $auth, CodeVerifier $codeVerifier, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->appHelper    = $appHelper;
		$this->auth         = $auth;
		$this->codeVerifier = $codeVerifier;
		$this->session      = $session;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if (($request['action'] ?? '') == 'recover') {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_recovery');

			$uid           = $this->session->getLocalUserId();
			$recovery_code = $request['recovery_code'] ?? '';

			if (is_string($recovery_code) && $this->codeVerifier->useRecoveryCode($uid, $recovery_code)) {
				$this->session->set('2fa', true);
				DI::sysmsg()->addInfo($this->t('Remaining recovery codes: %d', RecoveryCode::countValidForUser($uid)));

				$this->auth->setForUser(User::getById($uid), true, true);

				$this->baseUrl->redirect($this->session->pop('return_path', ''));
			} elseif ($this->codeVerifier->isRecoveryLockedOut($uid)) {
				$this->errors[] = $this->t('Too many failed attempts, please try again later.');
			} else {
				$this->errors[] = $this->t('Invalid code, please retry.');
			}
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect();
		}

		// Already authenticated with 2FA token
		if ($this->session->get('2fa')) {
			$this->baseUrl->redirect();
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/recovery.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_recovery'),

			'$title'            => $this->t('Two-factor recovery'),
			'$errors_label'     => $this->tt('Error', 'Errors', count($this->errors)),
			'$errors'           => $this->errors,
			'$message'          => $this->t('<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'),
			'$recovery_message' => $this->t('Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>', '2fa/recovery'),
			'$recovery_code'    => ['recovery_code', $this->t('Please enter a recovery code'), '', '', '', 'placeholder="000000-000000"'],
			'$recovery_label'   => $this->t('Submit recovery code and complete login'),
		]);
	}
}
