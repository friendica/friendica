<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Lock\Type;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Lock\Enum\Type;
use Friendica\Core\Lock\Exception\InvalidLockDriverException;
use Friendica\Core\System;

class SemaphoreLock extends AbstractLock
{
	private static $semaphore = [];

	public function __construct()
	{
		if (!function_exists('sem_get')) {
			throw new InvalidLockDriverException('Semaphore lock not supported');
		}
	}

	/**
	 * (@inheritdoc)
	 */
	private static function semaphoreKey($key)
	{
		$success = true;

		$temp = System::getTempPath();

		$file = $temp . '/' . $key . '.sem';

		if (!file_exists($file)) {
			$success = !empty(file_put_contents($file, $key));
		}

		return $success ? ftok($file, 'f') : false;
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire(string $key, int $timeout = 120, int $ttl = Duration::FIVE_MINUTES): bool
	{
		self::$semaphore[$key] = sem_get(self::semaphoreKey($key));
		if (!empty(self::$semaphore[$key])) {
			if (sem_acquire(self::$semaphore[$key], ($timeout === 0))) {
				$this->markAcquire($key);
				return true;
			}
		}

		return false;
	}

	/**
	 * (@inheritdoc)
	 *
	 * @param bool $override not necessary parameter for semaphore locks since the lock lives as long as the execution
	 *                       of the using function
	 */
	public function release(string $key, bool $override = false): bool
	{
		$success = false;

		if (!empty(self::$semaphore[$key])) {
			try {
				$success = @sem_release(self::$semaphore[$key]);
				unset(self::$semaphore[$key]);
				$this->markRelease($key);
			} catch (\Exception) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked(string $key): bool
	{
		return isset(self::$semaphore[$key]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return Type::SEMAPHORE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = ''): array
	{
		// We can just return our own semaphore keys, since we don't know
		// the state of other semaphores, even if the .sem files exists
		$keys = array_keys(self::$semaphore);

		if (empty($prefix)) {
			return $keys;
		} else {
			$result = [];

			foreach ($keys as $key) {
				if (str_starts_with($key, $prefix)) {
					array_push($result, $key);
				}
			}

			return $result;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll(bool $override = false): bool
	{
		// Semaphores are just alive during a run, so there is no need to release
		// You can just release your own locks
		return parent::releaseAll($override);
	}
}
