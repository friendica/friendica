<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Core\Lock\Type;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Core\Lock\Exception\LockPersistenceException;

class CacheLock extends AbstractLock
{
	/**
	 * @var string The static prefix of all locks inside the cache
	 */
	const CACHE_PREFIX = 'lock:';

	/**
	 * @var ICanCache;
	 */
	private $cache;

	/**
	 * CacheLock constructor.
	 *
	 * @param ICanCacheInMemory $cache The CacheDriver for this type of lock
	 */
	public function __construct(ICanCacheInMemory $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire(string $key, int $timeout = 120, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$got_lock = false;
		$start    = time();

		$lockKey = self::getLockKey($key);

		try {
			do {
				$lock = $this->cache->get($lockKey);
				// When we do want to lock something that was already locked by us.
				if ((int)$lock == getmypid()) {
					$got_lock = true;
				}

				// When we do want to lock something new
				if (is_null($lock)) {
					// At first initialize it with "0"
					$this->cache->add($lockKey, 0);
					// Now the value has to be "0" because otherwise the key was used by another process meanwhile
					if ($this->cache->compareSet($lockKey, 0, getmypid(), $ttl)) {
						$got_lock = true;
						$this->markAcquire($key);
					}
				}

				if (!$got_lock && ($timeout > 0)) {
					usleep(rand(10000, 200000));
				}
			} while (!$got_lock && ((time() - $start) < $timeout));
		} catch (CachePersistenceException $exception) {
			throw new LockPersistenceException(sprintf('Cannot acquire lock for key %s', $key), $exception);
		}

		return $got_lock;
	}

	/**
	 * (@inheritdoc)
	 */
	public function release(string $key, bool $override = false): bool
	{
		$lockKey = self::getLockKey($key);

		try {
			if ($override) {
				$return = $this->cache->delete($lockKey);
			} else {
				$return = $this->cache->compareDelete($lockKey, getmypid());
			}
		} catch (CachePersistenceException $exception) {
			throw new LockPersistenceException(sprintf('Cannot release lock for key %s (override %b)', $key, $override), $exception);
		}
		$this->markRelease($key);

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked(string $key): bool
	{
		$lockKey = self::getLockKey($key);
		try {
			$lock = $this->cache->get($lockKey);
		} catch (CachePersistenceException $exception) {
			throw new LockPersistenceException(sprintf('Cannot check lock state for key %s', $key), $exception);
		}
		return isset($lock) && ($lock !== false);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return $this->cache->getName();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = ''): array
	{
		try {
			$locks = $this->cache->getAllKeys(self::CACHE_PREFIX . $prefix);
		} catch (CachePersistenceException $exception) {
			throw new LockPersistenceException(sprintf('Cannot get locks with prefix %s', $prefix), $exception);
		}

		array_walk($locks, function (&$lock) {
			$lock = substr($lock, strlen(self::CACHE_PREFIX));
		});

		return $locks;
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll(bool $override = false): bool
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
	private static function getLockKey(string $key): string
	{
		return self::CACHE_PREFIX . $key;
	}
}
