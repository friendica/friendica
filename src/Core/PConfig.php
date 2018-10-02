<?php
/**
 * User Configuration Class
 *
 * @file include/Core/PConfig.php
 *
 * @brief Contains the class with methods for user configuration
 */
namespace Friendica\Core;

use Friendica\App;
use Friendica\BaseObject;

require_once 'include/dba.php';

/**
 * @brief Management of user configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The PConfig::get() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 */
class PConfig extends BaseObject
{
	/**
	 * @var Friendica\Core\Config\IPConfigAdapter
	 */
	private static $adapter = null;

	public static function init($uid)
	{
		// Database isn't ready or populated yet
		if (!(self::getApp()->hasMode(App::MODE_DBCONFIGAVAILABLE))) {
			return;
		}

		if (self::getApp()->getConfigValue('system', 'config_adapter') == 'preload') {
			self::$adapter = new Config\PreloadPConfigAdapter($uid);
		} else {
			self::$adapter = new Config\JITPConfigAdapter($uid);
		}
	}

	/**
	 * @brief Loads all configuration values of a user's config family into a cached storage.
	 *
	 * All configuration values of the given user are stored in global cache
	 * which is available under the global variable $a->config[$uid].
	 *
	 * @param string $uid    The user_id
	 * @param string $family The category of the configuration value
	 *
	 * @return void
	 */
	public static function load($uid, $family)
	{
		// Database isn't ready or populated yet
		if (!(self::getApp()->hasMode(App::MODE_DBCONFIGAVAILABLE))) {
			return;
		}

		if (empty(self::$adapter)) {
			self::init($uid);
		}

		self::$adapter->load($uid, $family);
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular user's config value from the given category ($family)
	 * and the $key from a cached storage in $a->config[$uid].
	 *
	 * @param string  $uid           The user_id
	 * @param string  $family        The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public static function get($uid, $family, $key, $default_value = null, $refresh = false)
	{
		// Database isn't ready or populated yet
		if (!(self::getApp()->hasMode(App::MODE_DBCONFIGAVAILABLE))) {
			return;
		}

		if (empty(self::$adapter)) {
			self::init($uid);
		}

		return self::$adapter->get($uid, $family, $key, $default_value, $refresh);
	}

	/**
	 * @brief Sets a configuration value for a user
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * @note Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $uid    The user_id
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param string $value  The value to store
	 *
	 * @return bool Operation success
	 */
	public static function set($uid, $family, $key, $value)
	{
		// Database isn't ready or populated yet
		if (!(self::getApp()->hasMode(App::MODE_DBCONFIGAVAILABLE))) {
			return false;
		}

		if (empty(self::$adapter)) {
			self::init($uid);
		}

		return self::$adapter->set($uid, $family, $key, $value);
	}

	/**
	 * @brief Deletes the given key from the users's configuration.
	 *
	 * Removes the configured value from the stored cache in $a->config[$uid]
	 * and removes it from the database.
	 *
	 * @param string $uid    The user_id
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return mixed
	 */
	public static function delete($uid, $family, $key)
	{
		// Database isn't ready or populated yet
		if (!(self::getApp()->hasMode(App::MODE_DBCONFIGAVAILABLE))) {
			return false;
		}

		if (empty(self::$adapter)) {
			self::init($uid);
		}

		return self::$adapter->delete($uid, $family, $key);
	}
}
