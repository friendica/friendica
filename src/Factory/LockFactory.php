<?php

namespace Friendica\Factory;

use Friendica\Core\Cache\Cache;
use Friendica\Core\Cache\IMemoryCache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Lock;
use Friendica\Database\Database;
use Psr\Log\LoggerInterface;

/**
 * Class LockFactory
 *
 * @package Friendica\Core\Cache
 *
 * A basic class to generate a LockDriver
 */
class LockFactory
{
	/**
	 * @var string The default driver for caching
	 */
	const DEFAULT_DRIVER = 'default';

	/**
	 * @var IConfiguration The configuration to read parameters out of the config
	 */
	private $config;

	/**
	 * @var Database The database connection in case that the cache is used the dba connection
	 */
	private $dba;

	/**
	 * @var CacheFactory The memory cache driver in case we use it
	 */
	private $cacheFactory;

	/**
	 * @var LoggerInterface The Friendica Logger
	 */
	private $logger;

	public function __construct(CacheFactory $cacheFactory, IConfiguration $config, Database $dba, LoggerInterface $logger)
	{
		$this->cacheFactory = $cacheFactory;
		$this->config       = $config;
		$this->dba          = $dba;
		$this->logger       = $logger;
	}

	public function create()
	{
		$lock_type = $this->config->get('system', 'lock_driver', self::DEFAULT_DRIVER);

		try {
			switch ($lock_type) {
				case Cache::TYPE_MEMCACHE:
				case Cache::TYPE_MEMCACHED:
				case Cache::TYPE_REDIS:
				case Cache::TYPE_APCU:
					$cache = $this->cacheFactory->create($lock_type);
					if ($cache instanceof IMemoryCache) {
						return new Lock\CacheLock($cache);
					} else {
						throw new \Exception(sprintf('Incompatible cache driver \'%s\' for lock used', $lock_type));
					}
					break;

				case 'database':
					return new Lock\DatabaseLock($this->dba);
					break;

				case 'semaphore':
					return new Lock\SemaphoreLock();
					break;

				default:
					return self::useAutoDriver();
			}
		} catch (\Exception $exception) {
			$this->logger->alert('Driver \'' . $lock_type . '\' failed - Fallback to \'useAutoDriver()\'', ['exception' => $exception]);
			return self::useAutoDriver();
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
	 * @return Lock\ILock
	 */
	private function useAutoDriver()
	{
		// 1. Try to use Semaphores for - local - locking
		if (function_exists('sem_get')) {
			try {
				return new Lock\SemaphoreLock();
			} catch (\Exception $exception) {
				$this->logger->debug('Using Semaphore driver for locking failed.', ['exception' => $exception]);
			}
		}

		// 2. Try to use Cache Locking (don't use the DB-Cache Locking because it works different!)
		$cache_type = $this->config->get('system', 'cache_driver', 'database');
		if ($cache_type != Cache::TYPE_DATABASE) {
			try {
				$cache = $this->cacheFactory->create($cache_type);
				if ($cache instanceof IMemoryCache) {
					return new Lock\CacheLock($cache);
				}
			} catch (\Exception $exception) {
				$this->logger->debug('Using Cache driver for locking failed.', ['exception' => $exception]);
			}
		}

		// 3. Use Database Locking as a Fallback
		return new Lock\DatabaseLock($this->dba);
	}
}
