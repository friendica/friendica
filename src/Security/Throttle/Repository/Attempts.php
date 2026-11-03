<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\Throttle\Repository;

use Friendica\Core\Cache\Capability\ICanCache;

/**
 * Keeps recent requests and failed attempts in the cache.
 *
 * Subjects like IP addresses are only stored hashed.
 */
class Attempts
{
	public function __construct(
		private readonly ICanCache $cache,
	) {}

	public function hasRecentRequest(string $scope, string $subject): bool
	{
		return (bool) $this->cache->get($this->requestKey($scope, $subject));
	}

	public function recordRequest(string $scope, string $subject, int $ttl): void
	{
		$this->cache->set($this->requestKey($scope, $subject), true, $ttl);
	}

	public function countFailures(string $scope, int $uid): int
	{
		return (int) $this->cache->get($this->failureKey($scope, $uid));
	}

	/**
	 * The read and write aren't atomic, callers hold a lock.
	 */
	public function recordFailure(string $scope, int $uid, int $ttl): void
	{
		$this->cache->set($this->failureKey($scope, $uid), $this->countFailures($scope, $uid) + 1, $ttl);
	}

	public function resetFailures(string $scope, int $uid): void
	{
		$this->cache->delete($this->failureKey($scope, $uid));
	}

	private function requestKey(string $scope, string $subject): string
	{
		return 'throttle:' . $scope . ':request:' . hash('sha256', $subject);
	}

	private function failureKey(string $scope, int $uid): string
	{
		return 'throttle:' . $scope . ':failures:' . $uid;
	}
}
