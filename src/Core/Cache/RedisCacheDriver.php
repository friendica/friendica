<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;

use Exception;
use Redis;

/**
 * Redis Cache Driver. This driver is based on Memcache driver
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 * @author Roland Haeder <roland@mxchange.org>
 */
class RedisCacheDriver extends AbstractCacheDriver implements IMemoryCacheDriver
{
	/**
	 * @var Redis
	 */
	private $redis;

	public function __construct($redis_host, $redis_port)
	{
		if (!class_exists('Redis', false)) {
			throw new Exception('Redis class isn\'t available');
		}

		$this->redis = new Redis();

		if (!$this->redis->connect($redis_host, $redis_port)) {
			throw new Exception('Expected Redis server at ' . $redis_host . ':' . $redis_port . ' isn\'t available');
		}
	}

	public function get($key)
	{
		$return = null;
		$cachekey = $this->getCacheKey($key);

		$cached = $this->redis->get($cachekey);
		if ($cached === false && !$this->redis->exists($cachekey)) {
			return null;
		}

		$value = unserialize($cached);

		// Only return a value if the serialized value is valid.
		// We also check if the db entry is a serialized
		// boolean 'false' value (which we want to return).
		if ($cached === serialize(false) || $value !== false) {
			$return = $value;
		}

		return $return;
	}

	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		$cached = serialize($value);

		if ($ttl > 0) {
			return $this->redis->setex(
				$cachekey,
				$ttl,
				$cached
			);
		} else {
			return $this->redis->set(
				$cachekey,
				$cached
			);
		}
	}

	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return ($this->redis->delete($cachekey) > 0);
	}

	public function clear($outdated = true)
	{
		if ($outdated) {
			return true;
		} else {
			return $this->redis->flushAll();
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		$cached = serialize($value);

		return $this->redis->setnx($cachekey, $cached);
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		$newCached = serialize($newValue);

		$this->redis->watch($cachekey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $oldValue) {
			if ($ttl > 0) {
				$result = $this->redis->multi()
					->setex($cachekey, $ttl, $newCached)
					->exec();
			} else {
				$result = $this->redis->multi()
					->set($cachekey, $newValue)
					->exec();
			}
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}
	/**
	 * (@inheritdoc)
	 */
	public function compareDelete($key, $value)
	{
		$cachekey = $this->getCacheKey($key);

		$this->redis->watch($cachekey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $value) {
			$result = $this->redis->multi()
				->del($cachekey)
				->exec();
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}
}
