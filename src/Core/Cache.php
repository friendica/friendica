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

	/**
	 * @var Cache\ICacheDriver
	 */
	private static $driver = null;

	public static function init()
	{
		$driver_name = Config::get('system', 'cache_driver', 'database');

		self::$driver = CacheDriverFactory::create($driver_name);
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
		$time = microtime(true);

		$return = self::getDriver()->get($key);

		self::getApp()->save_timestamp($time, 'cache');

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

		self::getApp()->save_timestamp($time, 'cache_write');

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

		self::getApp()->save_timestamp($time, 'cache_write');

		return $return;
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
