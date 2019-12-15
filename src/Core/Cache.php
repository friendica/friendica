<?php
/**
 * @file src/Core/Cache.php
 */
namespace Friendica\Core;

use Friendica\Core\Cache\Cache as CacheClass;
use Friendica\DI;

/**
 * @brief Class for storing data for a short time
 */
class Cache
{
	/** @deprecated Use CacheClass::MONTH */
	const MONTH        = CacheClass::MONTH;
	/** @deprecated Use CacheClass::WEEK */
	const WEEK         = CacheClass::WEEK;
	/** @deprecated Use CacheClass::DAY */
	const DAY          = CacheClass::DAY;
	/** @deprecated Use CacheClass::HOUR */
	const HOUR         = CacheClass::HOUR;
	/** @deprecated Use CacheClass::HALF_HOUR */
	const HALF_HOUR    = CacheClass::HALF_HOUR;
	/** @deprecated Use CacheClass::QUARTER_HOUR */
	const QUARTER_HOUR = CacheClass::QUARTER_HOUR;
	/** @deprecated Use CacheClass::FIVE_MINUTES */
	const FIVE_MINUTES = CacheClass::FIVE_MINUTES;
	/** @deprecated Use CacheClass::MINUTE */
	const MINUTE       = CacheClass::MINUTE;
	/** @deprecated Use CacheClass::INFINITE */
	const INFINITE     = CacheClass::INFINITE;

	/**
	 * @brief Returns all the cache keys sorted alphabetically
	 *
	 * @param string $prefix Prefix of the keys (optional)
	 *
	 * @return array Empty if the driver doesn't support this feature
	 * @throws \Exception
	 */
	public static function getAllKeys($prefix = null)
	{
		return DI::cache()->getAllKeys($prefix);
	}

	/**
	 * @brief Fetch cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 * @throws \Exception
	 */
	public static function get($key)
	{
		return DI::cache()->get($key);
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
	 * @throws \Exception
	 */
	public static function set($key, $value, $duration = CacheClass::MONTH)
	{
		return DI::cache()->set($key, $value, $duration);
	}

	/**
	 * @brief Delete a value from the cache
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete($key)
	{
		return DI::cache()->delete($key);
	}

	/**
	 * @brief Remove outdated data from the cache
	 *
	 * @param boolean $outdated just remove outdated values
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function clear($outdated = true)
	{
		return DI::cache()->clear($outdated);
	}
}
