<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Security;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Security\Authentication;
use Friendica\Security\FormSecurityToken;
use Friendica\Security\LoginCode\EmailCodeLogin;
use Friendica\Security\LoginCode\ValueObject\Ticket;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Login with a one time code that is sent by e-mail
 */
final class LoginCode extends BaseModule
{
	private const SESSION_UID   = 'login_code_uid';
	private const SESSION_NONCE = 'login_code_nonce';
	private const FORM_TOKEN    = 'login_code';

	public function __construct(
		private readonly EmailCodeLogin $emailCodeLogin,
		private readonly Authentication $auth,
		private readonly FormSecurityToken $formSecurityToken,
		private readonly IHandleUserSessions $session,
		private readonly IManageConfigValues $config,
		private readonly SystemMessages $sysMessages,
		private readonly App\Request $request,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters,
		EventDispatcherInterface $eventDispatcher,
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
	}

	protected function post(array $request = [])
	{
		if (!$this->config->get('system', 'enable_login_code')) {
			$this->baseUrl->redirect('login');
		}

		if (!$this->formSecurityToken->isRequestValid($request, $this->server, self::FORM_TOKEN)) {
			$this->sysMessages->addNotice($this->formSecurityToken->getErrorMessage());
			$this->baseUrl->redirect($this->pagePath($request['return_path'] ?? ''));
		}

		$return_path = $request['return_path'] ?? '';

		switch ($request['action'] ?? '') {
			case 'request':
				$this->requestCode(trim($request['username'] ?? ''));
				break;
			case 'verify':
				$this->verifyCode(trim($request['code'] ?? ''), !empty($request['remember']), $return_path);
				break;
		}

		$this->baseUrl->redirect($this->pagePath($return_path));
	}

	protected function content(array $request = []): string
	{
		if (!$this->config->get('system', 'enable_login_code')) {
			$this->baseUrl->redirect('login');
		}

		if ($this->session->getLocalUserId()) {
			$this->baseUrl->redirect();
		}

		if (!empty($request['new'])) {
			$this->session->remove(self::SESSION_UID);
		}

		$return_path = $request['return_path'] ?? '';

		$data = [
			'$notices'             => $this->sysMessages->flushNotices(),
			'$infos'               => $this->sysMessages->flushInfos(),
			'$title'               => $this->t('Sign in with an e-mail code'),
			'$return_path'         => $return_path,
			'$back'                => $this->t('Sign in with a password'),
			'$form_security_token' => $this->formSecurityToken->create(self::FORM_TOKEN),
		];

		if ($this->session->get(self::SESSION_UID)) {
			$data += [
				'$step'     => 'verify',
				'$desc'     => $this->t('Enter the code we sent you by e-mail. It is valid for %d minutes.', intdiv(EmailCodeLogin::CODE_TTL, 60)),
				'$code'     => ['code', $this->t('Code'), '', '', $this->t('Required'), 'autofocus autocomplete="one-time-code" placeholder="000000" inputmode="numeric" pattern="[0-9]*"'],
				'$remember' => ['remember', $this->t('Remember me'), 0, ''],
				'$submit'   => $this->t('Sign in'),
				'$again'    => $this->t('Request a new code'),
				'$new_path' => 'login/code?' . http_build_query(['new' => 1, 'return_path' => $return_path]),
			];
		} else {
			$data += [
				'$step'     => 'request',
				'$desc'     => $this->t('Enter your username or e-mail address. We will send you a code to sign in without a password.'),
				'$username' => ['username', $this->t('Username or email'), '', '', $this->t('Required'), 'autofocus autocomplete="username"'],
				'$submit'   => $this->t('Send code'),
			];
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('security/login_code.tpl'), $data);
	}

	private function pagePath(string $return_path): string
	{
		return 'login/code' . ($return_path !== '' ? '?' . http_build_query(['return_path' => $return_path]) : '');
	}

	private function requestCode(string $login): void
	{
		if ($login === '') {
			return;
		}

		$ticket = $this->emailCodeLogin->request($login, $this->request->getRemoteAddress(), (string) $this->session->get(self::SESSION_NONCE));
		$this->session->set(self::SESSION_UID, $ticket->uid);
		$this->session->set(self::SESSION_NONCE, $ticket->nonce);

		// The answer doesn't depend on the existence of the account, to not reveal which accounts exist
		$this->sysMessages->addInfo($this->t('If an account with this name exists and a code hasn\'t been requested recently, we have sent a code by e-mail.'));
	}

	private function verifyCode(#[\SensitiveParameter] string $code, bool $remember, string $return_path): void
	{
		$ticket = new Ticket((int) $this->session->get(self::SESSION_UID), (string) $this->session->get(self::SESSION_NONCE));
		if (!$this->emailCodeLogin->verify($ticket, $code, $this->request->getRemoteAddress())) {
			$this->sysMessages->addNotice($this->t('Invalid or expired code, please retry.'));
			return;
		}

		$this->auth->withLoginCode($ticket->uid, $remember, $return_path);
	}
}
