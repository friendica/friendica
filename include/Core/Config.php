<?php
namespace Friendica\Core;

use dbm;

/**
 * @file include/Core/Config.php
 *
 *  @brief Contains the class with methods for system configuration
 */


/**
 * @brief Arbitrary sytem configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The Config::get() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 *
 * There are a few places in the code (such as the admin panel) where boolean
 * configurations need to be fixed as of 10/08/2011.
 */
class Config {

	private static $cache;
	private static $in_db;

	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * All configuration values of the system are stored in global cache
	 * which is available under the global variable $a->config
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @return void
	 */
	public static function load($family = "config") {

		// We don't preload "system" anymore.
		// This reduces the number of database reads a lot.
		if ($family === 'system') {
			return;
		}

		$a = get_app();

		$r = q("SELECT `v`, `k` FROM `config` WHERE `cat` = '%s'", dbesc($family));
		if (dbm::is_result($r)) {
			foreach ($r as $rr) {
				$k = $rr['k'];
				if ($family === 'config') {
					$a->config[$k] = $rr['v'];
				} else {
					$a->config[$family][$k] = $rr['v'];
					self::$cache[$family][$k] = $rr['v'];
					self::$in_db[$family][$k] = true;
				}
			}
		}
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular config value from the given category ($family)
	 * and the $key from a cached storage in $a->config[$uid].
	 * $instore is only used by the set_config function
	 * to determine if the key already exists in the DB
	 * If a key is found in the DB but doesn't exist in
	 * local config cache, pull it into the cache so we don't have
	 * to hit the DB again for this item.
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to query
	 * @param mixed $default_value optional
	 *  The value to return if key is not set (default: null)
	 * @param boolean $refresh optional
	 *  If true the config is loaded from the db and not from the cache (default: false)
	 * @return mixed Stored value or null if it does not exist
	 */
	public static function get($family, $key, $default_value = null, $refresh = false) {

		$a = get_app();

		if (!$refresh) {

			// Do we have the cached value? Then return it
			if (isset(self::$cache[$family][$key])) {
				if (self::$cache[$family][$key] === '!<unset>!') {
					return $default_value;
				} else {
					return self::$cache[$family][$key];
				}
			}
		}

		$ret = q("SELECT `v` FROM `config` WHERE `cat` = '%s' AND `k` = '%s'",
			dbesc($family),
			dbesc($key)
		);
		if (dbm::is_result($ret)) {
			// manage array value
			$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v']) ? unserialize($ret[0]['v']) : $ret[0]['v']);

			// Assign the value from the database to the cache
			self::$cache[$family][$key] = $val;
			self::$in_db[$family][$key] = true;
			return $val;
		} elseif (isset($a->config[$family][$key])) {

			// Assign the value (mostly) from the .htconfig.php to the cache
			self::$cache[$family][$key] = $a->config[$family][$key];
			self::$in_db[$family][$key] = false;

			return $a->config[$family][$key];
		}

		self::$cache[$family][$key] = '!<unset>!';
		self::$in_db[$family][$key] = false;

		return $default_value;
	}

	/**
	 * @brief Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to set
	 * @param string $value
	 *  The value to store
	 * @return mixed Stored $value or false if the database update failed
	 */
	public static function set($family, $key, $value) {
		$a = get_app();

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$dbvalue = (!is_array($value) ? (string)$value : $value);

		$stored = self::get($family, $key, null, true);

		if (($stored === $dbvalue) AND self::$in_db[$family][$key]) {
			return true;
		}

		if ($family === 'config') {
			$a->config[$key] = $dbvalue;
		} elseif ($family != 'system') {
			$a->config[$family][$key] = $dbvalue;
		}

		// Assign the just added value to the cache
		self::$cache[$family][$key] = $dbvalue;

		// manage array value
		$dbvalue = (is_array($value) ? serialize($value) : $dbvalue);

		if (is_null($stored) OR !self::$in_db[$family][$key]) {
			$ret = q("INSERT INTO `config` (`cat`, `k`, `v`) VALUES ('%s', '%s', '%s') ON DUPLICATE KEY UPDATE `v` = '%s'",
				dbesc($family),
				dbesc($key),
				dbesc($dbvalue),
				dbesc($dbvalue)
			);
		} else {
			$ret = q("UPDATE `config` SET `v` = '%s' WHERE `cat` = '%s' AND `k` = '%s'",
				dbesc($dbvalue),
				dbesc($family),
				dbesc($key)
			);
		}
		if ($ret) {
			self::$in_db[$family][$key] = true;
			return $value;
		}
		return $ret;
	}

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in $a->config
	 * and removes it from the database.
	 *
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to delete
	 * @return mixed
	 */
	public static function delete($family, $key) {

		if (isset(self::$cache[$family][$key])) {
			unset(self::$cache[$family][$key]);
			unset(self::$in_db[$family][$key]);
		}
		$ret = q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s'",
			dbesc($family),
			dbesc($key)
		);

		return $ret;
	}
}
