<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\LoginCode;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Security\Account\ValueObject\Account;
use Friendica\Util\Emailer;
use Friendica\Util\Strings;

/**
 * Sends a login code to the owner of an account.
 */
class CodeMail
{
	public function __construct(
		private readonly Emailer $emailer,
		private readonly IManageConfigValues $config,
		private readonly L10n $l10n,
	) {}

	public function send(Account $account, #[\SensitiveParameter] string $code, int $validMinutes): bool
	{
		$sitename = $this->config->get('config', 'sitename');
		$preamble = Strings::deindent($this->l10n->t('
			Dear %1$s,
				A request was recently received at "%2$s" to sign in to your account
			with a one time code.

			If you did NOT request this, please ignore and/or delete this email.
			Nobody can sign in without this code.', $account->username, $sitename));
		$body = Strings::deindent($this->l10n->t('
			Your code is:

			%1$s

			It is valid for %2$d minutes and can only be used once.', $code, $validMinutes));

		$email = $this->emailer->newSystemMail()
			->withMessage($this->l10n->t('Your sign in code for %s', $sitename), $preamble, $body)
			->forUser(['uid' => $account->uid, 'language' => $account->language])
			->withRecipient($account->email)
			->build();

		return $this->emailer->send($email);
	}
}
