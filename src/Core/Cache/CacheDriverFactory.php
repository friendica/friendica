<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Config;

/**
 * Class CacheDriverFactory
 *
 * @package Friendica\Core\Cache
 *
 * A basic class to generate a CacheDriver
 */
class CacheDriverFactory
{
	/**
	 * This method creates a CacheDriver for the given cache driver name
	 *
	 * @param string $driver The name of the cache driver
	 * @return ICacheDriver  The instance of the CacheDriver
	 * @throws \Exception    The exception if something went wrong during the CacheDriver creation
	 */
	public static function create($driver) {

		switch ($driver) {
			case 'memcache':
				$memcache_host = Config::get('system', 'memcache_host');
				$memcache_port = Config::get('system', 'memcache_port');

				return new MemcacheCacheDriver($memcache_host, $memcache_port);
				break;

			case 'memcached':
				$memcached_hosts = Config::get('system', 'memcached_hosts');

				return new MemcachedCacheDriver($memcached_hosts);
				break;
			case 'redis':
				$redis_host = Config::get('system', 'redis_host');
				$redis_port = Config::get('system', 'redis_port');

				return new RedisCacheDriver($redis_host, $redis_port);
				break;
			default:
				return new DatabaseCacheDriver();
		}
	}
}
