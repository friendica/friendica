<?php
/**
 * @file include/config.php
 *
 *  @brief (Deprecated) Arbitrary configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The get_?config() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 *
 * There are a few places in the code (such as the admin panel) where boolean
 * configurations need to be fixed as of 10/08/2011.
 */

use Friendica\Core\Config;
use Friendica\Core\PConfig;

/**
 * @brief (Deprecated) Loads all configuration values of family into a cached storage.
 *
 * Note: This function is deprecated. Use Config::load() instead.
 *
 * @param string $family
 *  The category of the configuration value
 * @return void
 */
function load_config($family) {
	return Config::load($family);
}

/**
 * @brief (Deprecated) Get a particular user's config variable given the category name
 * ($family) and a key.
 *
 * Note: This function is deprecated. Use Config::get() instead.
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @param boolean $refresh
 *  If true the config is loaded from the db and not from the cache
 * @return mixed Stored value or false if it does not exist
 */
function get_config($family, $key, $refresh = false) {
	$v = Config::get($family, $key, false, $refresh);
	return $v;
}

/**
 * @brief (Deprecated) Sets a configuration value for system config
 *
 * Note: This function is deprecated. Use Config::set() instead.
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to set
 * @param string $value
 *  The value to store
 * @return mixed Stored $value or false if the database update failed
 */
function set_config($family,$key,$value) {
	return Config::set($family, $key, $value);
}

/**
 * @brief (Deprecated) Deletes the given key from the system configuration.
 *
 * Note: This function is deprecated. Use Config::delete() instead.
 *
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to delete
 * @return mixed
 */
function del_config($family,$key) {
	return Config::delete($family, $key);
}

/**
 * @brief (Deprecated) Loads all configuration values of a user's config family into a cached storage.
 *
 * Note: This function is deprecated. Use PConfig::load() instead.
 *
 * @param string $uid
 *  The user_id
 * @param string $family
 *  The category of the configuration value
 * @return void
 */
function load_pconfig($uid,$family) {
	return PConfig::load($uid, $family);
}

/**
 * @brief (Deprecated) Get a particular user's config variable given the category name
 * ($family) and a key.
 *
 * Note: This function is deprecated. Use PConfig::get() instead.
 *
 * @param string $uid
 *  The user_id
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to query
 * @param boolean $refresh
 *  If true the config is loaded from the db and not from the cache
 * @return mixed Stored value or false if it does not exist
 */
function get_pconfig($uid, $family, $key, $refresh = false) {
	$v = PConfig::get($uid, $family, $key, false, $refresh);
	return $v;
}

/**
 * @brief (Deprecated) Sets a configuration value for a user
 *
 * Note: This function is deprecated. Use PConfig::set() instead.
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
function set_pconfig($uid,$family,$key,$value) {
	return PConfig::set($uid, $family, $key, $value);
}

/**
 * @brief (Deprecated) Deletes the given key from the users's configuration.
 *
 * Note: This function is deprecated. Use PConfig::delete() instead.
 *
 * @param string $uid The user_id
 * @param string $family
 *  The category of the configuration value
 * @param string $key
 *  The configuration key to delete
 * @return mixed
 */
function del_pconfig($uid,$family,$key) {
	return PConfig::delete($uid, $family, $key);
}
