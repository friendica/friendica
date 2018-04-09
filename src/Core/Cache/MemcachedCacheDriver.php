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

	public function __construct(array $memcached_hosts)
	{
		if (!class_exists('Memcached', false)) {
			throw new \Exception('Memcached class isn\'t available');
		}

		$this->memcached = new \Memcached();

		$this->memcached->addServers($memcached_hosts);

		if (count($this->memcached->getServerList()) == 0) {
			throw new \Exception('Expected Memcached servers aren\'t available, config:' . var_export($memcached_hosts, true));
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
			time() + $duration
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
