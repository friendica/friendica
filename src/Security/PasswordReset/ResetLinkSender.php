<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\PasswordReset;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Model\User;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\ValueObject\Limit;
use Friendica\Util\Emailer;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Mails a password reset link to an account.
 */
final readonly class ResetLinkSender
{
	/** Minimum time between two reset mails to an account in seconds */
	public const ACCOUNT_REQUEST_INTERVAL = 900;

	public function __construct(
		private AttemptLimiter $limiter,
		private IManageConfigValues $config,
		private BaseURL $baseUrl,
		private L10n $l10n,
		private Emailer $emailer,
		private LoggerInterface $logger,
	) {}

	/**
	 * Sends the link unless the account doesn't exist or got one recently.
	 *
	 * @param string $login Email address or nickname
	 */
	public function send(string $login): void
	{
		$user = User::getForPasswordReset($login);
		if ($user === null) {
			return;
		}

		$accountLimit = new Limit('lostpass-account', 0, 0, self::ACCOUNT_REQUEST_INTERVAL);
		if (!$this->limiter->tryRequest($accountLimit, 'uid:' . $user['uid'])) {
			return;
		}

		$token = Strings::getRandomHex(32);
		if (!User::setPasswordResetToken($user['uid'], hash('sha256', $token))) {
			$this->logger->warning('Password reset token could not be stored', ['uid' => $user['uid']]);
			return;
		}

		$l10n     = $user['language'] ? $this->l10n->withLang($user['language']) : $this->l10n;
		$sitename = $this->config->get('config', 'sitename');

		$preamble = Strings::deindent($l10n->t('
			Dear %1$s,
				A request was recently received at "%2$s" to reset your account
			password. In order to confirm this request, please select the verification link
			below or paste it into your web browser address bar.

			If you did NOT request this change, please DO NOT follow the link
			provided and ignore and/or delete this email, the request will expire shortly.

			Your password will not be changed unless we can verify that you
			issued this request.', $user['username'], $sitename));
		$body = Strings::deindent($l10n->t('
			Follow this link soon to verify your identity:

			%1$s

			You will then be able to choose a new password.

			The login details are as follows:

			URL: %2$s
			Username: %3$s', $this->baseUrl . '/lostpass/' . $token, (string) $this->baseUrl, $user['nickname']));

		$email = $this->emailer->newSystemMail()
			->withMessage($l10n->t('Password reset requested at %s', $sitename), $preamble, $body)
			->forUser($user)
			->withRecipient($user['email'])
			->build();

		$this->emailer->send($email);
	}
}
