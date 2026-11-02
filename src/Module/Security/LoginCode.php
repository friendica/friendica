<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Security;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Security\Authentication;
use Friendica\Util\Emailer;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Login with a one time code that is sent by e-mail
 */
final class LoginCode extends BaseModule
{
	/** Lifetime of a code in seconds */
	private const CODE_TTL = 600;
	/** Minimum time between two code requests in seconds */
	private const REQUEST_INTERVAL = 60;
	/** Number of wrong codes after which the code is discarded */
	private const MAX_ATTEMPTS = 5;

	private const SESSION_UID = 'login_code_uid';

	public function __construct(
		private readonly Authentication $auth,
		private readonly IHandleUserSessions $session,
		private readonly ICanCache $cache,
		private readonly Database $dba,
		private readonly Emailer $emailer,
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
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		if (!$this->config->get('system', 'enable_login_code')) {
			$this->baseUrl->redirect('login');
		}

		switch ($request['action'] ?? '') {
			case 'request':
				$this->requestCode(trim($request['username'] ?? ''));
				break;
			case 'verify':
				$this->verifyCode(trim($request['code'] ?? ''), !empty($request['remember']), $request['return_path'] ?? '');
				break;
		}

		$this->baseUrl->redirect('login/code');
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

		$notices = $this->sysMessages->flushNotices();
		$infos   = $this->sysMessages->flushInfos();

		$data = [
			'$notices'     => $notices,
			'$infos'       => $infos,
			'$title'       => $this->t('Sign in with an e-mail code'),
			'$return_path' => $request['return_path'] ?? '',
			'$back'        => $this->t('Sign in with a password'),
		];

		if ($this->session->get(self::SESSION_UID)) {
			$data += [
				'$step'     => 'verify',
				'$desc'     => $this->t('Enter the code we sent you by e-mail. It is valid for %d minutes.', self::CODE_TTL / 60),
				'$code'     => ['code', $this->t('Code'), '', '', $this->t('Required'), 'autofocus autocomplete="one-time-code" placeholder="000000" inputmode="numeric" pattern="[0-9]*"'],
				'$remember' => ['remember', $this->t('Remember me'), 0, ''],
				'$submit'   => $this->t('Sign in'),
				'$again'    => $this->t('Request a new code'),
			];
		} else {
			$data += [
				'$step'     => 'request',
				'$desc'     => $this->t('Enter your username or e-mail address. We will send you a code to sign in without a password.'),
				'$username' => ['username', $this->t('Username or email'), '', '', $this->t('Required'), 'autofocus'],
				'$submit'   => $this->t('Send code'),
			];
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('security/login_code.tpl'), $data);
	}

	private function requestCode(string $username): void
	{
		if ($username === '') {
			return;
		}

		// The answer doesn't depend on the existence of the account, to not reveal which accounts exist
		$this->sysMessages->addInfo($this->t('If an account with this name exists and a code hasn\'t been requested recently, we have sent a code by e-mail.'));

		$condition = ['(`email` = ? OR `nickname` = ?) AND `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`', $username, $username];
		$user      = $this->dba->selectFirst('user', ['uid', 'username', 'nickname', 'email', 'language'], $condition);
		if (!$this->dba->isResult($user)) {
			$this->logger->notice('Login code requested for unknown account', ['ip' => $this->request->getRemoteAddress()]);
			// Continue with the second step, so that this is indistinguishable from an existing account
			$this->session->set(self::SESSION_UID, -1);
			return;
		}

		$this->session->set(self::SESSION_UID, (int) $user['uid']);

		$ipKey  = 'login-code-request:ip:' . hash('sha256', $this->request->getRemoteAddress());
		$uidKey = 'login-code-request:uid:' . $user['uid'];
		if ($this->cache->get($ipKey) || $this->cache->get($uidKey)) {
			$this->logger->notice('Login code requested too often', ['uid' => $user['uid'], 'ip' => $this->request->getRemoteAddress()]);
			return;
		}
		$this->cache->set($ipKey, true, self::REQUEST_INTERVAL);
		$this->cache->set($uidKey, true, self::REQUEST_INTERVAL);

		$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		$this->cache->set($this->codeKey((int) $user['uid']), ['hash' => hash('sha256', $code), 'attempts' => 0], self::CODE_TTL);

		$sitename = $this->config->get('config', 'sitename');
		$preamble = Strings::deindent($this->t('
			Dear %1$s,
				A request was recently received at "%2$s" to sign in to your account
			with a one time code.

			If you did NOT request this, please ignore and/or delete this email.
			Nobody can sign in without this code.', $user['username'], $sitename));
		$body = Strings::deindent($this->t('
			Your code is:

			%1$s

			It is valid for %2$d minutes and can only be used once.', $code, self::CODE_TTL / 60));

		$email = $this->emailer->newSystemMail()
			->withMessage($this->t('Your sign in code for %s', $sitename), $preamble, $body)
			->forUser($user)
			->withRecipient($user['email'])
			->build();

		$this->emailer->send($email);

		$this->logger->info('Login code sent', ['uid' => $user['uid'], 'ip' => $this->request->getRemoteAddress()]);
	}

	private function verifyCode(string $code, bool $remember, string $return_path): void
	{
		$uid = (int) $this->session->get(self::SESSION_UID);
		if (!$uid) {
			return;
		}

		$key   = $this->codeKey($uid);
		$entry = $this->cache->get($key);

		if (!is_array($entry) || $code === '' || !hash_equals($entry['hash'], hash('sha256', $code))) {
			if (is_array($entry)) {
				$entry['attempts']++;
				if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
					$this->cache->delete($key);
				} else {
					$this->cache->set($key, $entry, self::CODE_TTL);
				}
			}

			$this->logger->notice('Login with invalid code', ['uid' => $uid, 'ip' => $this->request->getRemoteAddress()]);
			$this->sysMessages->addNotice($this->t('Invalid or expired code, please retry.'));
			return;
		}

		// A code can only be used once
		$this->cache->delete($key);

		// Like in the password login, don't keep anything (like a 2FA verification) from a previous session
		$this->session->clear();

		$this->logger->info('Login with code', ['uid' => $uid, 'ip' => $this->request->getRemoteAddress()]);

		$this->auth->withLoginCode($uid, $remember, $return_path);
	}

	private function codeKey(int $uid): string
	{
		return 'login-code:' . $uid;
	}
}
