<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;

use \Exception;

/**
 * Memcached Cache Driver
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class MemcachedCacheDriver extends AbstractCacheDriver implements IMemoryCacheDriver
{
	use TraitCompareSet;
	use TraitCompareDelete;

	/**
	 * @var \Memcached
	 */
	private $memcached;

	public function __construct(array $memcached_hosts)
	{
		if (!class_exists('\Memcached', false)) {
			throw new Exception('Memcached class isn\'t available');
		}

		$this->memcached = new \Memcached();

		$this->memcached->addServers($memcached_hosts);

		if (count($this->memcached->getServerList()) == 0) {
			throw new Exception('Expected Memcached servers aren\'t available, config:' . var_export($memcached_hosts, true));
		}
	}

	public function get($key)
	{
		$return = null;
		$cachekey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$value = $this->memcached->get($cachekey);

		if ($this->memcached->getResultCode() === \Memcached::RES_SUCCESS) {
			$return = $value;
		}

		return $return;
	}

	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->memcached->set(
				$cachekey,
				$value,
				$ttl
			);
		} else {
			return $this->memcached->set(
				$cachekey,
				$value
			);
		}

	}

	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->delete($cachekey);
	}

	public function clear($outdated = true)
	{
		if ($outdated) {
			return true;
		} else {
			return $this->memcached->flush();
		}
	}

	/**
	 * @brief Sets a value if it's not already stored
	 *
	 * @param string $key      The cache key
	 * @param mixed  $value    The old value we know from the cache
	 * @param int    $ttl      The cache lifespan, must be one of the Cache constants
	 * @return bool
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->add($cachekey, $value, $ttl);
	}
}
