<?php
/**
 * @file src/Core/Cache.php
 */
namespace Friendica\Core;

use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @brief Class for storing data for a short time
 */
class Cache extends \Friendica\BaseObject
{
	const MONTH        = 2592000;
	const WEEK         = 604800;
	const DAY          = 86400;
	const HOUR         = 3600;
	const HALF_HOUR    = 1800;
	const QUARTER_HOUR = 900;
	const FIVE_MINUTES = 300;
	const MINUTE       = 60;
	const INFINITE     = 0;

	/**
	 * @var Cache\ICacheDriver
	 */
	private static $driver       = null;
	public  static $driver_class = null;
	public  static $driver_name  = null;

	public static function init()
	{
		self::$driver_name  = Config::get('system', 'cache_driver', 'database');
		self::$driver       = CacheDriverFactory::create(self::$driver_name);
		self::$driver_class = get_class(self::$driver);
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
	 * @brief Returns all the cache keys sorted alphabetically
	 *
	 * @param string $prefix Prefix of the keys (optional)
	 *
	 * @return array Empty if the driver doesn't support this feature
	 */
	public static function getAllKeys($prefix = null)
	{
		$time = microtime(true);

		$return = self::getDriver()->getAllKeys($prefix);

		self::getApp()->saveTimestamp($time, 'cache');

		return $return;
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
		$time = microtime(true);

		$return = self::getDriver()->get($key);

		self::getApp()->saveTimestamp($time, 'cache');

		return $return;
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
		$time = microtime(true);

		$return = self::getDriver()->set($key, $value, $duration);

		self::getApp()->saveTimestamp($time, 'cache_write');

		return $return;
	}

	/**
	 * @brief Delete a value from the cache
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return bool
	 */
	public static function delete($key)
	{
		$time = microtime(true);

		$return = self::getDriver()->delete($key);

		self::getApp()->saveTimestamp($time, 'cache_write');

		return $return;
	}

	/**
	 * @brief Remove outdated data from the cache
	 *
	 * @param boolean $outdated just remove outdated values
	 *
	 * @return bool
	 */
	public static function clear($outdated = true)
	{
		return self::getDriver()->clear($outdated);
	}
}
