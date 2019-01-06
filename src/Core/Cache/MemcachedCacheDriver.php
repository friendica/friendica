<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;
use Friendica\Core\Logger;

use Exception;
use Memcached;

/**
 * Memcached Cache Driver
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class MemcachedCacheDriver extends AbstractCacheDriver implements IMemoryCacheDriver
{
	use TraitCompareSet;
	use TraitCompareDelete;

	/**
	 * @var \Memcached
	 */
	private $memcached;

	/**
	 * Due to limitations of the INI format, the expected configuration for Memcached servers is the following:
	 * array {
	 *   0 => "hostname, port(, weight)",
	 *   1 => ...
	 * }
	 *
	 * @param array $memcached_hosts
	 * @throws \Exception
	 */
	public function __construct(array $memcached_hosts)
	{
		if (!class_exists('Memcached', false)) {
			throw new Exception('Memcached class isn\'t available');
		}

		$this->memcached = new Memcached();

		array_walk($memcached_hosts, function (&$value) {
			if (is_string($value)) {
				$value = array_map('trim', explode(',', $value));
			}
		});

		$this->memcached->addServers($memcached_hosts);

		if (count($this->memcached->getServerList()) == 0) {
			throw new Exception('Expected Memcached servers aren\'t available, config:' . var_export($memcached_hosts, true));
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		$keys = $this->getOriginalKeys($this->memcached->getAllKeys());

		if ($this->memcached->getResultCode() == Memcached::RES_SUCCESS) {
			return $this->filterArrayKeysByPrefix($keys, $prefix);
		} else {
			Logger::log('Memcached \'getAllKeys\' failed with ' . $this->memcached->getResultMessage(), Logger::ALL);
			return [];
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$return = null;
		$cachekey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$value = $this->memcached->get($cachekey);

		if ($this->memcached->getResultCode() === Memcached::RES_SUCCESS) {
			$return = $value;
		} else {
			Logger::log('Memcached \'get\' failed with ' . $this->memcached->getResultMessage(), Logger::ALL);
		}

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
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

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->delete($cachekey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		if ($outdated) {
			return true;
		} else {
			return $this->memcached->flush();
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->add($cachekey, $value, $ttl);
	}
}
