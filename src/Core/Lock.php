<?php

namespace Friendica\Core;

/**
 * @file src/Core/Lock.php
 * @brief Functions for preventing parallel execution of functions
 */

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Cache\IMemoryCacheDriver;

/**
 * @brief This class contain Functions for preventing parallel execution of functions
 */
class Lock
{
	/**
	 * @var Lock\ILockDriver;
	 */
	static $driver = null;

	public static function init()
	{
		$lock_driver = Config::get('system', 'lock_driver', 'default');

		try {
			switch ($lock_driver) {
				case 'memcache':
				case 'memcached':
				case 'redis':
					$cache_driver = CacheDriverFactory::create($lock_driver);
					if ($cache_driver instanceof IMemoryCacheDriver) {
						self::$driver = new Lock\CacheLockDriver($cache_driver);
					}
					break;

				case 'database':
					self::$driver = new Lock\DatabaseLockDriver();
					break;

				case 'semaphore':
					self::$driver = new Lock\SemaphoreLockDriver();
					break;

				default:
					self::useAutoDriver();
			}
		} catch (\Exception $exception) {
			logger ('Driver \'' . $lock_driver . '\' failed - Fallback to \'useAutoDriver()\'');
			self::useAutoDriver();
		}
	}

	/**
	 * @brief This method tries to find the best - local - locking method for Friendica
	 *
	 * The following sequence will be tried:
	 * 1. Semaphore Locking
	 * 2. Cache Locking
	 * 3. Database Locking
	 *
	 */
	private static function useAutoDriver() {

		// 1. Try to use Semaphores for - local - locking
		if (function_exists('sem_get')) {
			try {
				self::$driver = new Lock\SemaphoreLockDriver();
				return;
			} catch (\Exception $exception) {
				logger ('Using Semaphore driver for locking failed: ' . $exception->getMessage());
			}
		}

		// 2. Try to use Cache Locking (don't use the DB-Cache Locking because it works different!)
		$cache_driver = Config::get('system', 'cache_driver', 'database');
		if ($cache_driver != 'database') {
			try {
				$lock_driver = CacheDriverFactory::create($cache_driver);
				if ($lock_driver instanceof IMemoryCacheDriver) {
					self::$driver = new Lock\CacheLockDriver($lock_driver);
				}
				return;
			} catch (\Exception $exception) {
				logger('Using Cache driver for locking failed: ' . $exception->getMessage());
			}
		}

		// 3. Use Database Locking as a Fallback
		self::$driver = new Lock\DatabaseLockDriver();
	}

	/**
	 * Returns the current cache driver
	 *
	 * @return Lock\ILockDriver;
	 */
	private static function getDriver()
	{
		if (self::$driver === null) {
			self::init();
		}

		return self::$driver;
	}

	/**
	 * @brief Acquires a lock for a given name
	 *
	 * @param string  $key Name of the lock
	 * @param integer $timeout Seconds until we give up
	 *
	 * @return boolean Was the lock successful?
	 */
	public static function acquire($key, $timeout = 120)
	{
		return self::getDriver()->acquireLock($key, $timeout);
	}

	/**
	 * @brief Releases a lock if it was set by us
	 *
	 * @param string $key Name of the lock
	 * @return void
	 */
	public static function release($key)
	{
		self::getDriver()->releaseLock($key);
	}

	/**
	 * @brief Releases all lock that were set by us
	 * @return void
	 */
	public static function releaseAll()
	{
		self::getDriver()->releaseAll();
	}
}
