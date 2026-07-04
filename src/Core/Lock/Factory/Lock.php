<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Lock\Factory;

use Friendica\Core\Cache\Factory\Cache;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Type as CacheType;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type as LockType;
use Friendica\Database\Database;
use Psr\Log\LoggerInterface;

/**
 * Class LockFactory
 *
 * @package Friendica\Core\Cache
 *
 * A basic class to generate a LockDriver
 */
class Lock
{
	/**
	 * @var string The default driver for caching
	 */
	public const DEFAULT_DRIVER = 'default';

	public function __construct(
		/**
		 * @var Cache The memory cache driver in case we use it
		 */
		private readonly Cache $cacheFactory,
		/**
		 * @var IManageConfigValues The configuration to read parameters out of the config
		 */
		private readonly IManageConfigValues $config,
		/**
		 * @var Database The database connection in case that the cache is used the dba connection
		 */
		private readonly Database $dba,
		/**
		 * @var LoggerInterface The Friendica Logger
		 */
		private readonly LoggerInterface $logger,
	) {}

	public function create()
	{
		$lock_type = $this->config->get('system', 'lock_driver', self::DEFAULT_DRIVER);

		try {
			switch ($lock_type) {
				case CacheType\MemcacheCache::NAME:
				case CacheType\MemcachedCache::NAME:
				case CacheType\RedisCache::NAME:
				case CacheType\APCuCache::NAME:
					$cache = $this->cacheFactory->createLocal($lock_type);
					if ($cache instanceof ICanCacheInMemory) {
						return new LockType\CacheLock($cache);
					} else {
						throw new \Exception(sprintf('Incompatible cache driver \'%s\' for lock used', $lock_type));
					}
					// no break
				case 'database':
					return new LockType\DatabaseLock($this->dba);
				case 'semaphore':
					return new LockType\SemaphoreLock();
				default:
					return self::useAutoDriver();
			}
		} catch (\Exception $exception) {
			$this->logger->alert('Driver \'' . $lock_type . '\' failed - Fallback to \'useAutoDriver()\'', ['exception' => $exception]);
			return self::useAutoDriver();
		}
	}

	/**
	 * This method tries to find the best - local - locking method for Friendica
	 *
	 * The following sequence will be tried:
	 * 1. Semaphore Locking
	 * 2. Cache Locking
	 * 3. Database Locking
	 *
	 * @return ICanLock
	 */
	private function useAutoDriver()
	{
		// 1. Try to use Semaphores for - local - locking
		if (function_exists('sem_get')) {
			try {
				return new LockType\SemaphoreLock();
			} catch (\Exception $exception) {
				$this->logger->warning('Using Semaphore driver for locking failed.', ['exception' => $exception]);
			}
		}

		// 2. Try to use Cache Locking (don't use the DB-Cache Locking because it works different!)
		$cache_type = $this->config->get('system', 'cache_driver', 'database');
		if ($cache_type != CacheType\DatabaseCache::NAME) {
			try {
				$cache = $this->cacheFactory->createLocal($cache_type);
				if ($cache instanceof ICanCacheInMemory) {
					return new LockType\CacheLock($cache);
				}
			} catch (\Exception $exception) {
				$this->logger->warning('Using Cache driver for locking failed.', ['exception' => $exception]);
			}
		}

		// 3. Use Database Locking as a Fallback
		return new LockType\DatabaseLock($this->dba);
	}
}
