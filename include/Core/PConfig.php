<?php
namespace Friendica\Core;

use dbm;

/**
 * @file include/Core/PConfig.php
 * @brief contains the class with methods for the management
 * of the user configuration
 */

/**
 * @brief Management of user configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The PConfig::get() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 */
class PConfig {

	/**
	 * @brief Loads all configuration values of a user's config family into a cached storage.
	 *
	 * All configuration values of the given user are stored in global cache
	 * which is available under the global variable $a->config[$uid].
	 *
	 * @param string $uid
	 *  The user_id
	 * @param string $family
	 *  The category of the configuration value
	 * @return void
	 */
	public static function load($uid, $family) {
		$a = get_app();
		$r = q("SELECT `v`,`k` FROM `pconfig` WHERE `cat` = '%s' AND `uid` = %d ORDER BY `cat`, `k`, `id`",
			dbesc($family),
			intval($uid)
		);
		if (dbm::is_result($r)) {
			foreach ($r as $rr) {
				$k = $rr['k'];
				$a->config[$uid][$family][$k] = $rr['v'];
			}
		} else if ($family != 'config') {
			// Negative caching
			$a->config[$uid][$family] = "!<unset>!";
		}
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular user's config value from the given category ($family)
	 * and the $key from a cached storage in $a->config[$uid].
	 *
	 * @param string $uid
	 *  The user_id
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
	public static function get($uid, $family, $key, $default_value = null, $refresh = false) {

		$a = get_app();

		if (!$refresh) {
			// Looking if the whole family isn't set
			if (isset($a->config[$uid][$family])) {
				if ($a->config[$uid][$family] === '!<unset>!') {
					return $default_value;
				}
			}

			if (isset($a->config[$uid][$family][$key])) {
				if ($a->config[$uid][$family][$key] === '!<unset>!') {
					return $default_value;
				}
				return $a->config[$uid][$family][$key];
			}
		}

		$ret = q("SELECT `v` FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' ORDER BY `id` DESC LIMIT 1",
			intval($uid),
			dbesc($family),
			dbesc($key)
		);

		if (count($ret)) {
			$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
			$a->config[$uid][$family][$key] = $val;

			return $val;
		} else {
			$a->config[$uid][$family][$key] = '!<unset>!';
		}
		return $default_value;
	}

	/**
	 * @brief Sets a configuration value for a user
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * @note Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $uid
	 *  The user_id
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to set
	 * @param string $value
	 *  The value to store
	 * @return mixed Stored $value or false
	 */
	public static function set($uid, $family, $key, $value) {

		$a = get_app();

		$stored = self::get($uid, $family, $key);

		if ($stored == $value) {
			return true;
		}

		// manage array value
		$dbvalue = (is_array($value) ? serialize($value):$value);

		$a->config[$uid][$family][$key] = $value;

                if (is_null($stored)) {
			$ret = q("INSERT INTO `pconfig` (`uid`, `cat`, `k`, `v`) VALUES (%d, '%s', '%s', '%s') ON DUPLICATE KEY UPDATE `v` = '%s'",
				intval($uid),
				dbesc($family),
				dbesc($key),
				dbesc($dbvalue),
				dbesc($dbvalue)
			);
		} else {
			$ret = q("UPDATE `pconfig` SET `v` = '%s' WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s'",
				dbesc($dbvalue),
				intval($uid),
				dbesc($family),
				dbesc($key)
			);
		}

		if ($ret) {
			return $value;
		}
		return $ret;
	}

	/**
	 * @brief Deletes the given key from the users's configuration.
	 *
	 * Removes the configured value from the stored cache in $a->config[$uid]
	 * and removes it from the database.
	 *
	 * @param string $uid The user_id
	 * @param string $family
	 *  The category of the configuration value
	 * @param string $key
	 *  The configuration key to delete
	 * @return mixed
	 */
	public static function delete($uid,$family,$key) {

		$a = get_app();

		if (x($a->config[$uid][$family], $key)) {
			unset($a->config[$uid][$family][$key]);
		}

		$ret = q("DELETE FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s'",
			intval($uid),
			dbesc($family),
			dbesc($key)
		);

		return $ret;
	}
}
