<?php
/**
 * System Configuration Class
 *
 * @file include/Core/Config.php
 *
 * @brief Contains the class with methods for system configuration
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Core\Config;

require_once 'include/dba.php';

/**
 * @brief Arbitrary system configuration storage
 *
 * Note:
 * If we ever would decide to return exactly the variable type as entered,
 * we will have fun with the additional features. :-)
 */
class Config extends BaseObject
{
	/**
	 * @var Friendica\Core\Config\IConfigAdapter
	 */
	private static $adapter = null;

	public static function init()
	{
		if (self::getApp()->getConfigValue('system', 'config_adapter') == 'preload') {
			self::$adapter = new Config\PreloadConfigAdapter();
		} else {
			self::$adapter = new Config\JITConfigAdapter();
		}
	}

	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * All configuration values of the system are stored in global cache
	 * which is available under the global variable $a->config
	 *
	 * @param string $family The category of the configuration value
	 *
	 * @return void
	 */
	public static function load($family = "config")
	{
		if (empty(self::$adapter)) {
			self::init();
		}

		self::$adapter->load($family);
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
	 * @param string  $family        The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public static function get($family, $key, $default_value = null, $refresh = false)
	{
		if (empty(self::$adapter)) {
			self::init();
		}

		return self::$adapter->get($family, $key, $default_value, $refresh);
	}

	/**
	 * @brief Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return mixed Stored $value or false if the database update failed
	 */
	public static function set($family, $key, $value)
	{
		if (empty(self::$adapter)) {
			self::init();
		}

		return self::$adapter->set($family, $key, $value);
	}

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in $a->config
	 * and removes it from the database.
	 *
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return mixed
	 */
	public static function delete($family, $key)
	{
		if (empty(self::$adapter)) {
			self::init();
		}

		return self::$adapter->delete($family, $key);
	}
}
