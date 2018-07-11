<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;

class SemaphoreLockDriver extends AbstractLockDriver
{
	private static $semaphore = [];

	public function __construct()
	{
		if (!function_exists('sem_get')) {
			throw new \Exception('Semaphore lock not supported');
		}
	}

	/**
	 * (@inheritdoc)
	 */
	private static function semaphoreKey($key)
	{
		$temp = get_temppath();

		$file = $temp . '/' . $key . '.sem';

		if (!file_exists($file)) {
			file_put_contents($file, $key);
		}

		return ftok($file, 'f');
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache::FIVE_MINUTES)
	{
		self::$semaphore[$key] = sem_get(self::semaphoreKey($key));
		if (self::$semaphore[$key]) {
			if (sem_acquire(self::$semaphore[$key], ($timeout == 0))) {
				$this->markAcquire($key);
				return true;
			}
		}

		return false;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseLock($key)
	{
		if (empty(self::$semaphore[$key])) {
			return false;
		} else {
			$success = @sem_release(self::$semaphore[$key]);
			unset(self::$semaphore[$key]);
			$this->markRelease($key);
			return $success;
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		return isset(self::$semaphore[$key]);
	}
}
