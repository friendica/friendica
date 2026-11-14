<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\TwoFactor;

use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\ValueObject\Limit;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

/**
 * Checks the second factor of a login attempt, with a limit on failed attempts per account.
 */
final readonly class CodeVerifier
{
	public const MAX_FAILURES   = 10;
	public const FAILURE_WINDOW = 3600;

	/** Personal config key of the last accepted authenticator time step */
	public const LAST_TIMESTEP = 'last_timestep';

	private Limit $totpLimit;
	private Limit $recoveryLimit;

	public function __construct(
		private AttemptLimiter $limiter,
		private IManagePersonalConfigValues $pConfig,
		private Google2FA $google2fa,
	) {
		$this->totpLimit     = new Limit('2fa-totp', self::MAX_FAILURES, self::FAILURE_WINDOW, 0);
		$this->recoveryLimit = new Limit('2fa-recovery', self::MAX_FAILURES, self::FAILURE_WINDOW, 0);
	}

	/**
	 * Checks an authenticator code, a code is only accepted once per account.
	 */
	public function verifyTotp(int $uid, string $code): bool
	{
		$secret = $this->pConfig->get($uid, '2fa', 'secret');
		if (!is_string($secret) || $secret === '') {
			return false;
		}

		return $this->limiter->attempt($this->totpLimit, $uid, function () use ($uid, $code, $secret): bool {
			// Parallel requests may have changed the value since it was loaded, so it's read inside the lock
			$lastTimestep = (int) $this->pConfig->get($uid, '2fa', self::LAST_TIMESTEP, 0, true);

			$timestep = $this->google2fa->verifyKeyNewer($secret, $code, $lastTimestep);
			if ($timestep === false) {
				return false;
			}

			$this->pConfig->set($uid, '2fa', self::LAST_TIMESTEP, $timestep);
			return true;
		});
	}

	public function isTotpLockedOut(int $uid): bool
	{
		return $this->limiter->isLockedOut($this->totpLimit, $uid);
	}

	/**
	 * Checks a recovery code and marks it as used, a code is only accepted once.
	 */
	public function useRecoveryCode(int $uid, string $code): bool
	{
		return $this->limiter->attempt($this->recoveryLimit, $uid, fn (): bool => RecoveryCode::markUsedForUser($uid, $code));
	}

	public function isRecoveryLockedOut(int $uid): bool
	{
		return $this->limiter->isLockedOut($this->recoveryLimit, $uid);
	}
}
