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
		return $this->selectFailures($scope, $uid)['count'] ?? 0;
	}

	/**
	 * Counts the failure in a fixed window that starts with the first failure, later failures don't extend it.
	 *
	 * The read and write aren't atomic, callers hold a lock.
	 */
	public function recordFailure(string $scope, int $uid, int $window): void
	{
		$failures = $this->selectFailures($scope, $uid) ?? ['count' => 0, 'until' => time() + $window];

		$ttl = $failures['until'] - time();
		if ($ttl > 0) {
			$this->cache->set($this->failureKey($scope, $uid), ['count' => $failures['count'] + 1, 'until' => $failures['until']], $ttl);
		}
	}

	public function resetFailures(string $scope, int $uid): void
	{
		$this->cache->delete($this->failureKey($scope, $uid));
	}

	/**
	 * @return array{count: int, until: int}|null
	 */
	private function selectFailures(string $scope, int $uid): ?array
	{
		$failures = $this->cache->get($this->failureKey($scope, $uid));
		if (!is_array($failures) || !is_int($failures['count'] ?? null) || !is_int($failures['until'] ?? null) || $failures['until'] <= time()) {
			return null;
		}

		return $failures;
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
