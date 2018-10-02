<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;

/**
 * Cache Driver Interface
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
interface ICacheDriver
{
	/**
	 * Fetches cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 */
	public function get($key);

	/**
	 * Stores data in the cache identified by the key. The input $value can have multiple formats.
	 *
	 * @param string  $key      The cache key
	 * @param mixed   $value    The value to store
	 * @param integer $ttl The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 */
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES);

	/**
	 * Delete a key from the cache
	 *
	 * @param string $key      The cache key
	 *
	 * @return bool
	 */
	public function delete($key);

	/**
	 * Remove outdated data from the cache
	 * @param  boolean $outdated just remove outdated values
	 *
	 * @return bool
	 */
	public function clear($outdated = true);
}
