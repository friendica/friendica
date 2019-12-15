<?php
/**
 * User Configuration Class
 *
 * @file include/Core/PConfig.php
 *
 * @brief Contains the class with methods for user configuration
 */
namespace Friendica\Core;

use Friendica\DI;

/**
 * @brief Management of user configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The PConfig::get() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 */
class PConfig
{
	/**
	 * @brief Loads all configuration values of a user's config family into a cached storage.
	 *
	 * @param int    $uid The user_id
	 * @param string $cat The category of the configuration value
	 *
	 * @return void
	 */
	public static function load(int $uid, string $cat)
	{
		DI::pConfig()->load($uid, $cat);
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($cat) and a key.
	 *
	 * @param int     $uid           The user_id
	 * @param string  $cat           The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public static function get(int $uid, string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		return DI::pConfig()->get($uid, $cat, $key, $default_value, $refresh);
	}

	/**
	 * @brief Sets a configuration value for a user
	 *
	 * @param int    $uid    The user_id
	 * @param string $cat    The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return bool Operation success
	 */
	public static function set(int $uid, string $cat, string $key, $value)
	{
		return DI::pConfig()->set($uid, $cat, $key, $value);
	}

	/**
	 * @brief Deletes the given key from the users's configuration.
	 *
	 * @param int    $uid The user_id
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool
	 */
	public static function delete(int $uid, string $cat, string $key)
	{
		return DI::pConfig()->delete($uid, $cat, $key);
	}
}
