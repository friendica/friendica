<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;

/**
 * Trait TraitCompareSetDelete
 *
 * This Trait is to compensate non native "exclusive" sets/deletes in caches
 *
 * @package Friendica\Core\Cache
 */
trait TraitCompareSet
{
	abstract public function get($key);

	abstract public function set($key, $value, $ttl = Cache::FIVE_MINUTES);

	abstract public function delete($key);

	abstract public function add($key, $value, $ttl = Cache::FIVE_MINUTES);

	/**
	 * NonNative - Compares if the old value is set and sets the new value
	 *
	 * @param string $key         The cache key
	 * @param mixed  $oldValue    The old value we know from the cache
	 * @param mixed  $newValue    The new value we want to set
	 * @param int    $ttl      The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Cache::FIVE_MINUTES) {
		if ($this->add($key . "_lock", true)) {
			if ($this->get($key) === $oldValue) {
				$this->set($key, $newValue, $ttl);
				$this->delete($key . "_lock");
				return true;
 			} else {
				$this->delete($key . "_lock");
				return false;
			}
		} else {
			return false;
		}
	}
}
