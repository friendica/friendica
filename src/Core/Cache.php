<?php
/**
 * @file src/Core/Cache.php
 */
namespace Friendica\Core;

use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;
use dba;

require_once 'include/dba.php';

/**
 * @brief Class for storing data for a short time
 */
class Cache
{
	/**
	 * @brief Check for memcache and open a connection if configured
	 *
	 * @return object|boolean The memcache object - or "false" if not successful
	 */
	public static function memcache()
	{
		if (!function_exists('memcache_connect')) {
			return false;
		}

		if (!Config::get('system', 'memcache')) {
			return false;
		}

		$memcache_host = Config::get('system', 'memcache_host', '127.0.0.1');
		$memcache_port = Config::get('system', 'memcache_port', 11211);

		$memcache = new \Memcache;

		if (!$memcache->connect($memcache_host, $memcache_port)) {
			return false;
		}

		return $memcache;
	}

	/**
	 * @brief Return the duration for a given cache level
	 *
	 * @param integer $level Cache level
	 *
	 * @return integer The cache duration in seconds
	 */
	private static function duration($level)
	{
		switch ($level) {
			case CACHE_MONTH:
				$seconds = 2592000;
				break;
			case CACHE_WEEK:
				$seconds = 604800;
				break;
			case CACHE_DAY:
				$seconds = 86400;
				break;
			case CACHE_HOUR:
				$seconds = 3600;
				break;
			case CACHE_HALF_HOUR:
				$seconds = 1800;
				break;
			case CACHE_QUARTER_HOUR:
				$seconds = 900;
				break;
			case CACHE_FIVE_MINUTES:
				$seconds = 300;
				break;
			case CACHE_MINUTE:
				$seconds = 60;
				break;
		}
		return $seconds;
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
		$memcache = self::memcache();
		if (is_object($memcache)) {
			// We fetch with the hostname as key to avoid problems with other applications
			$cached = $memcache->get(get_app()->get_hostname().":".$key);
			$value = @unserialize($cached);

			// Only return a value if the serialized value is valid.
			// We also check if the db entry is a serialized
			// boolean 'false' value (which we want to return).
			if ($cached === serialize(false) || $value !== false) {
				return $value;
			}

			return null;
		}

		// Frequently clear cache
		self::clear();

		$cache = dba::selectFirst('cache', ['v'], ['k' => $key]);

		if (DBM::isResult($cache)) {
			$cached = $cache['v'];
			$value = @unserialize($cached);

			// Only return a value if the serialized value is valid.
			// We also check if the db entry is a serialized
			// boolean 'false' value (which we want to return).
			if ($cached === serialize(false) || $value !== false) {
				return $value;
			}
		}

		return null;
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
	 * @return void
	 */
	public static function set($key, $value, $duration = CACHE_MONTH)
	{
		// Do we have an installed memcache? Use it instead.
		$memcache = self::memcache();
		if (is_object($memcache)) {
			// We store with the hostname as key to avoid problems with other applications
			$memcache->set(get_app()->get_hostname().":".$key, serialize($value), MEMCACHE_COMPRESSED, self::duration($duration));
			return;
		}
		$fields = ['v' => serialize($value), 'expire_mode' => $duration, 'updated' => datetime_convert()];
		$condition = ['k' => $key];
		dba::update('cache', $fields, $condition, true);
	}

	/**
	 * @brief Remove outdated data from the cache
	 *
	 * @param integer $max_level The maximum cache level that is to be cleared
	 *
	 * @return void
	 */
	public static function clear($max_level = CACHE_MONTH)
	{
		// Clear long lasting cache entries only once a day
		if (Config::get("system", "cache_cleared_day") < time() - self::duration(CACHE_DAY)) {
			if ($max_level == CACHE_MONTH) {
				$condition = ["`updated` < ? AND `expire_mode` = ?",
						datetime_convert('UTC', 'UTC', "now - 30 days"),
						CACHE_MONTH];
				dba::delete('cache', $condition);
			}

			if ($max_level <= CACHE_WEEK) {
				$condition = ["`updated` < ? AND `expire_mode` = ?",
						datetime_convert('UTC', 'UTC', "now - 7 days"),
						CACHE_WEEK];
				dba::delete('cache', $condition);
			}

			if ($max_level <= CACHE_DAY) {
				$condition = ["`updated` < ? AND `expire_mode` = ?",
						datetime_convert('UTC', 'UTC', "now - 1 days"),
						CACHE_DAY];
				dba::delete('cache', $condition);
			}
			Config::set("system", "cache_cleared_day", time());
		}

		if (($max_level <= CACHE_HOUR) && (Config::get("system", "cache_cleared_hour")) < time() - self::duration(CACHE_HOUR)) {
			$condition = ["`updated` < ? AND `expire_mode` = ?",
					datetime_convert('UTC', 'UTC', "now - 1 hours"),
					CACHE_HOUR];
			dba::delete('cache', $condition);

			Config::set("system", "cache_cleared_hour", time());
		}

		if (($max_level <= CACHE_HALF_HOUR) && (Config::get("system", "cache_cleared_half_hour")) < time() - self::duration(CACHE_HALF_HOUR)) {
			$condition = ["`updated` < ? AND `expire_mode` = ?",
					datetime_convert('UTC', 'UTC', "now - 30 minutes"),
					CACHE_HALF_HOUR];
			dba::delete('cache', $condition);

			Config::set("system", "cache_cleared_half_hour", time());
		}

		if (($max_level <= CACHE_QUARTER_HOUR) && (Config::get("system", "cache_cleared_quarter_hour")) < time() - self::duration(CACHE_QUARTER_HOUR)) {
			$condition = ["`updated` < ? AND `expire_mode` = ?",
					datetime_convert('UTC', 'UTC', "now - 15 minutes"),
					CACHE_QUARTER_HOUR];
			dba::delete('cache', $condition);

			Config::set("system", "cache_cleared_quarter_hour", time());
		}

		if (($max_level <= CACHE_FIVE_MINUTES) && (Config::get("system", "cache_cleared_five_minute")) < time() - self::duration(CACHE_FIVE_MINUTES)) {
			$condition = ["`updated` < ? AND `expire_mode` = ?",
					datetime_convert('UTC', 'UTC', "now - 5 minutes"),
					CACHE_FIVE_MINUTES];
			dba::delete('cache', $condition);

			Config::set("system", "cache_cleared_five_minute", time());
		}

		if (($max_level <= CACHE_MINUTE) && (Config::get("system", "cache_cleared_minute")) < time() - self::duration(CACHE_MINUTE)) {
			$condition = ["`updated` < ? AND `expire_mode` = ?",
					datetime_convert('UTC', 'UTC', "now - 1 minutes"),
					CACHE_MINUTE];
			dba::delete('cache', $condition);

			Config::set("system", "cache_cleared_minute", time());
		}
	}
}
