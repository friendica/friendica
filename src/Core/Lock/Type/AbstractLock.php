<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Lock\Type;

use Friendica\Core\Lock\Capability\ICanLock;

/**
 * Basic class for Locking with common functions (local acquired locks, releaseAll, ..)
 */
abstract class AbstractLock implements ICanLock
{
	/**
	 * @var array The local acquired locks
	 */
	protected $acquiredLocks = [];

	/**
	 * Check if we've locally acquired a lock
	 *
	 * @param string $key The Name of the lock
	 *
	 * @return bool      Returns true if the lock is set
	 */
	protected function hasAcquiredLock(string $key): bool
	{
		return isset($this->acquiredLocks[$key]) && $this->acquiredLocks[$key] === true;
	}

	/**
	 * Mark a locally acquired lock
	 *
	 * @param string $key The Name of the lock
	 */
	protected function markAcquire(string $key)
	{
		$this->acquiredLocks[$key] = true;
	}

	/**
	 * Mark a release of a locally acquired lock
	 *
	 * @param string $key The Name of the lock
	 */
	protected function markRelease(string $key)
	{
		unset($this->acquiredLocks[$key]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll(bool $override = false): bool
	{
		$return = true;

		foreach ($this->acquiredLocks as $acquiredLock => $hasLock) {
			if (!$this->release($acquiredLock, $override)) {
				$return = false;
			}
		}

		return $return;
	}
}
