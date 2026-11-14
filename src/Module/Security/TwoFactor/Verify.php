<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Security\TwoFactor;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Security\TwoFactor\CodeVerifier;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Page 1: Authenticator code verification
 *
 * @package Friendica\Module\TwoFactor
 */
class Verify extends BaseModule
{
	protected $errors = [];

	/** @var CodeVerifier */
	protected $codeVerifier;
	/** @var IHandleUserSessions */
	protected $session;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, CodeVerifier $codeVerifier, IHandleUserSessions $session, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session      = $session;
		$this->codeVerifier = $codeVerifier;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if (($request['action'] ?? '') === 'verify') {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_verify');

			$uid  = $this->session->getLocalUserId();
			$code = $request['verify_code'] ?? '';

			if (is_string($code) && $this->codeVerifier->verifyTotp($uid, $code)) {
				$this->session->set('2fa', $code);

				$this->baseUrl->redirect('2fa/trust');
			} elseif ($this->codeVerifier->isTotpLockedOut($uid)) {
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

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/verify.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_verify'),

			'$title'            => $this->t('Two-factor authentication'),
			'$message'          => $this->t('<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'),
			'$errors_label'     => $this->tt('Error', 'Errors', count($this->errors)),
			'$errors'           => $this->errors,
			'$recovery_message' => $this->t('If you do not have access to your authentication code you can use a <a href="%s">two-factor recovery code</a>.', '2fa/recovery'),
			'$verify_code'      => ['verify_code', $this->t('Please enter a code from your authentication app'), '', '', $this->t('Required'), 'autofocus autocomplete="one-time-code" placeholder="000000" inputmode="numeric" pattern="[0-9]*"'],
			'$verify_label'     => $this->t('Verify code and complete login'),
		]);
	}
}
