<?php
/**
 * @file include/cache.php
 *
 * @brief Class for storing data for a short time
 */

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

class Cache {
	/**
	 * @brief Check for memcache and open a connection if configured
	 *
	 * @return object|boolean The memcache object - or "false" if not successful
	 */
	public static function memcache() {
		if (!function_exists('memcache_connect')) {
			return false;
		}

		if (!Config::get('system', 'memcache')) {
			return false;
		}

		$memcache_host = Config::get('system', 'memcache_host', '127.0.0.1');
		$memcache_port = Config::get('system', 'memcache_port', 11211);

		$memcache = new Memcache;

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
	private function duration($level) {
		switch($level) {
			case CACHE_MONTH;
				$seconds = 2592000;
				break;
			case CACHE_WEEK;
				$seconds = 604800;
				break;
			case CACHE_DAY;
				$seconds = 86400;
				break;
			case CACHE_HOUR;
				$seconds = 3600;
				break;
			case CACHE_HALF_HOUR;
				$seconds = 1800;
				break;
			case CACHE_QUARTER_HOUR;
				$seconds = 900;
				break;
			case CACHE_FIVE_MINUTES;
				$seconds = 300;
				break;
			case CACHE_MINUTE;
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
	public static function get($key) {

		$memcache = self::memcache();
		if (is_object($memcache)) {
			// We fetch with the hostname as key to avoid problems with other applications
			$value = $memcache->get(get_app()->get_hostname().":".$key);
			if (!is_bool($value)) {
				return unserialize($value);
			}

			return null;
		}

		// Frequently clear cache
		self::clear($duration);

		$r = q("SELECT `v` FROM `cache` WHERE `k`='%s' LIMIT 1",
			dbesc($key)
		);

		if (count($r)) {
			return $r[0]['v'];
		}

		return null;
	}

	/**
	 * @brief Put data in the cache according to the key
	 *
	 * @param string $key The key to the cached data
	 * @param mixed $valie The value that is about to be stored
	 * @param integer $duration The cache lifespan
	 */
	public static function set($key, $value, $duration = CACHE_MONTH) {

		// Do we have an installed memcache? Use it instead.
		$memcache = self::memcache();
		if (is_object($memcache)) {
			// We store with the hostname as key to avoid problems with other applications
			$memcache->set(get_app()->get_hostname().":".$key, serialize($value), MEMCACHE_COMPRESSED, self::duration($duration));
			return;
		}

		/// @todo store the cache data in the same way like the config data
		q("REPLACE INTO `cache` (`k`,`v`,`expire_mode`,`updated`) VALUES ('%s','%s',%d,'%s')",
				dbesc($key),
				dbesc($value),
				intval($duration),
				dbesc(datetime_convert()));
	}

	/**
	 * @brief Remove outdated data from the cache
	 *
	 * @param integer $maxlevel The maximum cache level that is to be cleared
	 */
	public static function clear($max_level = CACHE_MONTH) {

		// Clear long lasting cache entries only once a day
		if (get_config("system", "cache_cleared_day") < time() - self::duration(CACHE_DAY)) {
			if ($max_level == CACHE_MONTH) {
				q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
					dbesc(datetime_convert('UTC','UTC',"now - 30 days")), intval(CACHE_MONTH));
			}

			if ($max_level <= CACHE_WEEK) {
				q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
					dbesc(datetime_convert('UTC','UTC',"now - 7 days")), intval(CACHE_WEEK));
			}

			if ($max_level <= CACHE_DAY) {
				q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
				dbesc(datetime_convert('UTC','UTC',"now - 1 days")), intval(CACHE_DAY));
			}
			set_config("system", "cache_cleared_day", time());
		}

		if (($max_level <= CACHE_HOUR) AND (get_config("system", "cache_cleared_hour")) < time() - self::duration(CACHE_HOUR)) {
			q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
				dbesc(datetime_convert('UTC','UTC',"now - 1 hours")), intval(CACHE_HOUR));

			set_config("system", "cache_cleared_hour", time());
		}

		if (($max_level <= CACHE_HALF_HOUR) AND (get_config("system", "cache_cleared_half_hour")) < time() - self::duration(CACHE_HALF_HOUR)) {
			q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
				dbesc(datetime_convert('UTC','UTC',"now - 30 minutes")), intval(CACHE_HALF_HOUR));

			set_config("system", "cache_cleared_half_hour", time());
		}

		if (($max_level <= CACHE_QUARTER_HOUR) AND (get_config("system", "cache_cleared_hour")) < time() - self::duration(CACHE_QUARTER_HOUR)) {
			q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
				dbesc(datetime_convert('UTC','UTC',"now - 15 minutes")), intval(CACHE_QUARTER_HOUR));

			set_config("system", "cache_cleared_quarter_hour", time());
		}

		if (($max_level <= CACHE_FIVE_MINUTES) AND (get_config("system", "cache_cleared_five_minute")) < time() - self::duration(CACHE_FIVE_MINUTES)) {
			q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
				dbesc(datetime_convert('UTC','UTC',"now - 5 minutes")), intval(CACHE_FIVE_MINUTES));

			set_config("system", "cache_cleared_five_minute", time());
		}

		if (($max_level <= CACHE_MINUTE) AND (get_config("system", "cache_cleared_minute")) < time() - self::duration(CACHE_MINUTE)) {
			q("DELETE FROM `cache` WHERE `updated` < '%s' AND `expire_mode` = %d",
				dbesc(datetime_convert('UTC','UTC',"now - 1 minutes")), intval(CACHE_MINUTE));

			set_config("system", "cache_cleared_minute", time());
		}
	}
}
