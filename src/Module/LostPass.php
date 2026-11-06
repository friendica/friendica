<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Request;
use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Security\PasswordReset\ResetLinkSender;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\ValueObject\Limit;
use Friendica\Core\L10n;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Lost password module
 *
 * This module handles requests for a password reset link, the link leads to Security\ResetPassword.
 */
final class LostPass extends BaseModule
{
	/** Minimum time between two reset requests of an IP address in seconds */
	private const IP_REQUEST_INTERVAL = 60;

	/** Replaces the form with this message */
	private string $result = '';

	/**
	 * Initialize the module
	 *
	 * @param L10n $l10n
	 * @param BaseURL $baseUrl
	 * @param Arguments $args
	 * @param LoggerInterface $logger
	 * @param Profiler $profiler
	 * @param Response $response
	 * @param AttemptLimiter $attemptLimiter
	 * @param ResetLinkSender $resetLinkSender
	 * @param Request $request
	 * @param array $server
	 * @param array $parameters
	 */
	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, private readonly AttemptLimiter $attemptLimiter, private readonly ResetLinkSender $resetLinkSender, private readonly Request $request, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * Handle POST requests for password reset form submission
	 *
	 * @param array $request
	 * @return void
	 */
	protected function post(array $request = [])
	{
		self::checkFormSecurityTokenRedirectOnError('lostpass', 'lostpass');

		$loginame = $request['login-name'] ?? '';
		if (!is_string($loginame) || !mb_check_encoding($loginame, 'UTF-8') || trim($loginame) === '') {
			return;
		}

		$ipLimit = new Limit('lostpass-ip', 0, 0, self::IP_REQUEST_INTERVAL);
		if ($this->attemptLimiter->tryRequest($ipLimit, $this->request->getRemoteAddress())) {
			$this->resetLinkSender->send(trim($loginame));
		}

		// The same answer for every login, so it doesn't reveal if the account exists
		$this->result = $this->l10n->t('If the account exists, a message with a password reset link has been sent to its email address.');
	}

	/**
	 * Render page content
	 *
	 * @param array $request
	 * @return string Rendered page content
	 */
	protected function content(array $request = []): string
	{
		if ($this->result !== '') {
			return Renderer::replaceMacros(Renderer::getMarkupTemplate('lostpass_result.tpl'), [
				'$title'      => $this->l10n->t('Password Reset'),
				'$message'    => $this->result,
				'$link'       => 'login',
				'$link_label' => $this->l10n->t('Sign in'),
			]);
		}

		return $this->form();
	}

	/**
	 * Render the password reset form
	 *
	 * @return string Rendered form HTML
	 */
	private function form(): string
	{
		$tpl = Renderer::getMarkupTemplate('lostpass.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$title'  => $this->l10n->t('Forgot your Password?'),
			'$desc'   => $this->l10n->t('Enter your email address and submit to have your password reset. Then check your email for further instructions.'),
			'$name'   => $this->l10n->t('Username or email'),
			'$submit' => $this->l10n->t('Reset my password'),

			'$form_security_token' => self::getFormSecurityToken('lostpass'),
		]);

		return $o;
	}
}
