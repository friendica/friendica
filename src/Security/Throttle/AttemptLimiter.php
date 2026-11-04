<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\Throttle;

use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Security\Throttle\Repository\Attempts;
use Friendica\Security\Throttle\ValueObject\Limit;

/**
 * Protects short secrets against guessing.
 *
 * Failed attempts are counted per account, not per secret, so requesting a new secret doesn't reset the limit.
 * Every check runs under a lock per account, so parallel requests can't exceed the limit.
 * If the lock can't be acquired, the attempt fails.
 */
final readonly class AttemptLimiter
{
	private const LOCK_TIMEOUT = 5;

	public function __construct(
		private Attempts $attempts,
		private ICanLock $lock,
	) {}

	/**
	 * Records a request unless the subject made one within the request interval.
	 *
	 * @param string $subject E.g. an IP address or "uid:<uid>"
	 *
	 * @return bool Whether the request is allowed
	 */
	public function tryRequest(Limit $limit, string $subject): bool
	{
		$lockKey = 'throttle:' . $limit->scope . ':request:' . hash('sha256', $subject);
		if (!$this->lock->acquire($lockKey, self::LOCK_TIMEOUT)) {
			return false;
		}

		try {
			if ($this->attempts->hasRecentRequest($limit->scope, $subject)) {
				return false;
			}

			$this->attempts->recordRequest($limit->scope, $subject, $limit->requestInterval);
			return true;
		} finally {
			$this->lock->release($lockKey);
		}
	}

	public function isLockedOut(Limit $limit, int $uid): bool
	{
		return $this->attempts->countFailures($limit->scope, $uid) >= $limit->maxFailures;
	}

	/**
	 * Runs the check unless the account is locked out, and counts the result.
	 *
	 * @param \Closure(): bool $check Compares the secret, it runs while holding the account lock
	 */
	public function attempt(Limit $limit, int $uid, \Closure $check): bool
	{
		$lockKey = 'throttle:' . $limit->scope . ':' . $uid;
		if (!$this->lock->acquire($lockKey, self::LOCK_TIMEOUT)) {
			return false;
		}

		try {
			if ($this->isLockedOut($limit, $uid)) {
				return false;
			}

			if ($check()) {
				$this->attempts->resetFailures($limit->scope, $uid);
				return true;
			}

			$this->attempts->recordFailure($limit->scope, $uid, $limit->failureWindow);
			return false;
		} finally {
			$this->lock->release($lockKey);
		}
	}
}
