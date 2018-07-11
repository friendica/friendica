<?php

namespace Friendica\Core\Cache;
use Friendica\Core\Cache;

/**
 * This interface defines methods for Memory-Caches only
 *
 * Interface IMemoryCacheDriver
 *
 * @package Friendica\Core\Cache
 */
interface IMemoryCacheDriver extends ICacheDriver
{
	/**
	 * Sets a value if it's not already stored
	 *
	 * @param string $key      The cache key
	 * @param mixed  $value    The old value we know from the cache
	 * @param int    $ttl      The cache lifespan, must be one of the Cache constants
	 * @return bool
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES);

	/**
	 * Compares if the old value is set and sets the new value
	 *
	 * @param string $key         The cache key
	 * @param mixed  $oldValue    The old value we know from the cache
	 * @param mixed  $newValue    The new value we want to set
	 * @param int    $ttl      	   The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Cache::FIVE_MINUTES);

	/**
	 * Compares if the old value is set and removes it
	 *
	 * @param string $key          The cache key
	 * @param mixed  $value        The old value we know and want to delete
	 * @return bool
	 */
	public function compareDelete($key, $value);
}
