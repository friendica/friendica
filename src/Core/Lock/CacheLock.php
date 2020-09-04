<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Lock;

use Friendica\Core\BaseLock;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Cache\IMemoryCache;

class CacheLock extends BaseLock
{
	/**
	 * @var string The static prefix of all locks inside the cache
	 */
	const CACHE_PREFIX = 'lock:';

	/**
	 * @var \Friendica\Core\Cache\ICache;
	 */
	private $cache;

	/**
	 * @var int The id of this host
	 */
	private $hostId;

	/**
	 * CacheLock constructor.
	 *
	 * @param IMemoryCache $cache The CacheDriver for this type of lock
	 * @param int       $hostId   The id of the current host (used for locks)
	 */
	public function __construct(IMemoryCache $cache, int $hostId)
	{
		$this->cache  = $cache;
		$this->hostId = $hostId;
	}

	/**
	 * @param string $key The original key
	 *
	 * @return string        The cache key used for the cache
	 */
	private function getLockKey(string $key)
	{
		return $this->getLockPrefix() . $key;
	}

	/**
	 * @return string Returns the prefix of this lock instance
	 */
	private function getLockPrefix()
	{
		return self::CACHE_PREFIX . $this->hostId . ':';
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire($key, $timeout = 120, $ttl = Duration::FIVE_MINUTES)
	{
		$got_lock = false;
		$start    = time();

		$cachekey = $this->getLockKey($key);

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
		$cachekey = $this->getLockKey($key);

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
		$cachekey = $this->getLockKey($key);
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
		$locks = $this->cache->getAllKeys($this->getLockPrefix() . $prefix);

		array_walk($locks, function (&$lock, $key) {
			$lock = substr($lock, strlen($this->getLockPrefix()));
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
}
