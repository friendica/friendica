<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;
use Friendica\Core\Cache\IMemoryCache;

class CacheLock extends Lock
{
	/**
	 * @var string The static prefix of all locks inside the cache
	 */
	const CACHE_PREFIX = 'lock:';

	/**
	 * @var Cache\ICache;
	 */
	private $cache;

	/**
	 * CacheLock constructor.
	 *
	 * @param IMemoryCache $cache The CacheDriver for this type of lock
	 */
	public function __construct(IMemoryCache $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire($key, $timeout = 120, $ttl = Cache\Cache::FIVE_MINUTES)
	{
		$got_lock = false;
		$start    = time();

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
	public function release($key, $override = false)
	{
		$cachekey = self::getLockKey($key);

		if ($override) {
			$return = $this->cache->delete($cachekey);
		} else {
			$return = $this->cache->compareDelete($cachekey, getmypid());
		}
		$this->markRelease($key);

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		$cachekey = self::getLockKey($key);
		$lock     = $this->cache->get($cachekey);
		return isset($lock) && ($lock !== false);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return $this->cache->getName();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = '')
	{
		$locks = $this->cache->getAllKeys(self::CACHE_PREFIX . $prefix);

		array_walk($locks, function (&$lock, $key) {
			$lock = substr($lock, strlen(self::CACHE_PREFIX));
		});

		return $locks;
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll($override = false)
	{
		$success = parent::releaseAll($override);

		$locks = $this->getLocks();

		foreach ($locks as $lock) {
			if (!$this->release($lock, $override)) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * @param string $key The original key
	 *
	 * @return string        The cache key used for the cache
	 */
	private static function getLockKey($key)
	{
		return self::CACHE_PREFIX . $key;
	}
}
