<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Security\PasswordReset\ResetLinkSender;

/**
 * Sends a password reset link outside of the request, so the response time doesn't reveal if an account exists.
 */
class SendPasswordResetLink
{
	public static function execute(string $login): void
	{
		DI::getDice()->create(ResetLinkSender::class)->send($login);
	}
}
