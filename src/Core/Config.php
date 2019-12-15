<?php
/**
 * System Configuration Class
 *
 * @file include/Core/Config.php
 *
 * @brief Contains the class with methods for system configuration
 */
namespace Friendica\Core;

use Friendica\DI;

/**
 * @brief Arbitrary system configuration storage
 *
 * Note:
 * If we ever would decide to return exactly the variable type as entered,
 * we will have fun with the additional features. :-)
 */
class Config
{
	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * @param string $cat The category of the configuration value
	 *
	 * @return void
	 */
	public static function load($cat = "config")
	{
		DI::config()->load($cat);
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * @param string  $cat        The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public static function get($cat, $key, $default_value = null, $refresh = false)
	{
		return DI::config()->get($cat, $key, $default_value, $refresh);
	}

	/**
	 * @brief Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($cat) under the key ($key)
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return bool Operation success
	 */
	public static function set($cat, $key, $value)
	{
		return DI::config()->set($cat, $key, $value);
	}

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return bool
	 */
	public static function delete($cat, $key)
	{
		return DI::config()->delete($cat, $key);
	}
}
