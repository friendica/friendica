<?php

namespace Friendica\Core\Cache;

use Friendica\BaseObject;
use Friendica\Core\Cache;

/**
 * Memcached Cache Driver
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class MemcachedCacheDriver extends BaseObject implements ICacheDriver
{
	/**
	 * @var Memcached
	 */
	private $memcached;

	public function __construct($memcached_host, $memcached_port)
	{
		if (!class_exists('Memcached', false)) {
			throw new \Exception('Memcached class isn\'t available');
		}

		$this->memcached = new \Memcached();

		if (!$this->memcached->addServer($memcached_host, $memcached_port)) {
			throw new \Exception('Expected Memcached server at ' . $memcached_host . ':' . $memcached_port . ' isn\'t available');
		}
	}

	public function get($key)
	{
		$return = null;

		// We fetch with the hostname as key to avoid problems with other applications
		$value = $this->memcached->get(self::getApp()->get_hostname() . ':' . $key);

		if ($this->memcached->getResultCode() === \Memcached::RES_SUCCESS) {
			$return = $value;
		}

		return $return;
	}

	public function set($key, $value, $duration = Cache::MONTH)
	{
		// We store with the hostname as key to avoid problems with other applications
		return $this->memcached->set(
			self::getApp()->get_hostname() . ":" . $key,
			$value,
			Cache::duration($duration)
		);
	}

	public function delete($key)
	{
		return $this->memcached->delete($key);
	}

	public function clear()
	{
		return true;
	}
}
