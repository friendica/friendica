<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;
use Friendica\Core\Cache\IMemoryCacheDriver;

class CacheLockDriver extends AbstractLockDriver
{
	/**
	 * @var \Friendica\Core\Cache\ICacheDriver;
	 */
	private $cache;

	/**
	 * CacheLockDriver constructor.
	 *
	 * @param IMemoryCacheDriver $cache The CacheDriver for this type of lock
	 */
	public function __construct(IMemoryCacheDriver $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache::FIVE_MINUTES)
	{
		$got_lock = false;
		$start = time();

		$cachekey = self::getLockKey($key);

		do {
			$lock = $this->cache->get($cachekey);
			// When we do want to lock something that was already locked by us.
			if ((int)$lock == getmypid()) {
				$got_lock = true;
			}

			// When we do want to lock something new
			if (is_null($lock)) {
				// At first initialize it with "0"
				$this->cache->add($cachekey, 0);
				// Now the value has to be "0" because otherwise the key was used by another process meanwhile
				if ($this->cache->compareSet($cachekey, 0, getmypid(), $ttl)) {
					$got_lock = true;
					$this->markAcquire($key);
				}
			}

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(10000, 200000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseLock($key)
	{
		$cachekey = self::getLockKey($key);

		$this->cache->compareDelete($cachekey, getmypid());
		$this->markRelease($key);
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		$cachekey = self::getLockKey($key);
		$lock = $this->cache->get($cachekey);
		return isset($lock) && ($lock !== false);
	}

	/**
	 * @param string $key	The original key
	 * @return string		The cache key used for the cache
	 */
	private static function getLockKey($key) {
		return "lock:" . $key;
	}
}
