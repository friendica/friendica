<?php
/**
 * @file src/Core/Cache.php
 */
namespace Friendica\Core;

use Friendica\Core\Cache;
use Friendica\Core\Config;

/**
 * @brief Class for storing data for a short time
 */
class Cache
{
	const MONTH        = 0;
	const WEEK         = 1;
	const DAY          = 2;
	const HOUR         = 3;
	const HALF_HOUR    = 4;
	const QUARTER_HOUR = 5;
	const FIVE_MINUTES = 6;
	const MINUTE       = 7;

	/**
	 * @var Cache\ICacheDriver
	 */
	static $driver = null;

	public static function init()
	{
		switch(Config::get('system', 'cache_driver', 'database')) {
			case 'memcache':
				$memcache_host = Config::get('system', 'memcache_host', '127.0.0.1');
				$memcache_port = Config::get('system', 'memcache_port', 11211);

				self::$driver = new Cache\MemcacheCacheDriver($memcache_host, $memcache_port);
				break;
			case 'memcached':
				$memcached_host = Config::get('system', 'memcached_host', '127.0.0.1');
				$memcached_port = Config::get('system', 'memcached_port', 11211);

				self::$driver = new Cache\MemcachedCacheDriver($memcached_host, $memcached_port);
				break;
			default:
				self::$driver = new Cache\DatabaseCacheDriver();
		}
	}

	/**
	 * @brief Return the duration for a given cache level
	 *
	 * @param integer $level Cache level
	 *
	 * @return integer The cache duration in seconds
	 */
	public static function duration($level)
	{
		switch ($level) {
			case self::MONTH:
				$seconds = 2592000;
				break;
			case self::WEEK:
				$seconds = 604800;
				break;
			case self::DAY:
				$seconds = 86400;
				break;
			case self::HOUR:
				$seconds = 3600;
				break;
			case self::HALF_HOUR:
				$seconds = 1800;
				break;
			case self::QUARTER_HOUR:
				$seconds = 900;
				break;
			case self::FIVE_MINUTES:
				$seconds = 300;
				break;
			case self::MINUTE:
			default:
				$seconds = 60;
				break;
		}
		return $seconds;
	}

	/**
	 * Returns the current cache driver
	 *
	 * @return Cache\ICacheDriver
	 */
	private static function getDriver()
	{
		if (self::$driver === null) {
			self::init();
		}

		return self::$driver;
	}

	/**
	 * @brief Fetch cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 */
	public static function get($key)
	{
		return self::getDriver()->get($key);
	}

	/**
	 * @brief Put data in the cache according to the key
	 *
	 * The input $value can have multiple formats.
	 *
	 * @param string  $key      The key to the cached data
	 * @param mixed   $value    The value that is about to be stored
	 * @param integer $duration The cache lifespan
	 *
	 * @return bool
	 */
	public static function set($key, $value, $duration = self::MONTH)
	{
		return self::getDriver()->set($key, $value, $duration);
	}

	/**
	 * @brief Remove outdated data from the cache
	 *
	 * @param integer $max_level The maximum cache level that is to be cleared
	 *
	 * @return void
	 */
	public static function clear()
	{
		return self::getDriver()->clear();
	}
}
