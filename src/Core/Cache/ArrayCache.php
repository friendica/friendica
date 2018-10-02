<?php

namespace Friendica\Core\Cache;


use Friendica\Core\Cache;

/**
 * Implementation of the IMemoryCacheDriver mainly for testing purpose
 *
 * Class ArrayCache
 *
 * @package Friendica\Core\Cache
 */
class ArrayCache extends AbstractCacheDriver implements IMemoryCacheDriver
{
	use TraitCompareDelete;

	/** @var array Array with the cached data */
	protected $cachedData = array();

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		if (isset($this->cachedData[$key])) {
			return $this->cachedData[$key];
		}
		return null;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$this->cachedData[$key] = $value;
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		unset($this->cachedData[$key]);
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		// Array doesn't support TTL so just don't delete something
		if ($outdated) {
			return true;
		}

		$this->cachedData = [];
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		if (isset($this->cachedData[$key])) {
			return false;
		} else {
			return $this->set($key, $value, $ttl);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Cache::FIVE_MINUTES)
	{
		if ($this->get($key) === $oldValue) {
			return $this->set($key, $newValue);
		} else {
			return false;
		}
	}
}
