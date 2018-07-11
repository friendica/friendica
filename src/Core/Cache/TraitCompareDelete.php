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
trait TraitCompareDelete
{
	abstract public function get($key);

	abstract public function set($key, $value, $ttl = Cache::FIVE_MINUTES);

	abstract public function delete($key);

	abstract public function add($key, $value, $ttl = Cache::FIVE_MINUTES);

	/**
	 * NonNative - Compares if the old value is set and removes it
	 *
	 * @param string $key          The cache key
	 * @param mixed  $value        The old value we know and want to delete
	 * @return bool
	 */
	public function compareDelete($key, $value) {
		if ($this->add($key . "_lock", true)) {
			if ($this->get($key) === $value) {
				$this->delete($key);
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
