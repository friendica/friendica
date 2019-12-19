<?php

namespace Friendica\Core\Config;

/**
 * Interface for accessing user specific configurations
 */
interface IPConfiguration
{

	/**
	 * Loads all configuration values of a user's config family into a cached storage.
	 *
	 * All configuration values of the given user are stored with the $uid in the cache
	 *
	 * @param int $uid The user_id
	 * @param string $cat The category of the configuration value
	 *
	 * @return void
	 * @see PConfigCache
	 *
	 */
	function load(int $uid, string $cat = 'config');

	/**
	 * Get a particular user's config variable given the category name
	 * ($cat) and a key.
	 *
	 * Get a particular user's config value from the given category ($cat)
	 * and the $key with the $uid from a cached storage either from the $this->configAdapter
	 * (@see IConfigAdapter) or from the $this->configCache (@see PConfigCache).
	 *
	 * @param int     $uid           The user_id
	 * @param string  $cat           The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	function get(int $uid, string $cat, string $key, $default_value = null, bool $refresh = false);

	/**
	 * Sets a configuration value for a user
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * @note  Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param int    $uid   The user_id
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 */
	function set(int $uid, string $cat, string $key, $value);

	/**
	 * Deletes the given key from the users's configuration.
	 *
	 * Removes the configured value from the stored cache in $this->configCache
	 * (@see ConfigCache) and removes it from the database (@see IConfigAdapter)
	 *  with the given $uid.
	 *
	 * @param int $uid The user_id
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool
	 */
	function delete(int $uid, string $cat, string $key);


	/**
	 * Returns the Config Cache
	 *
	 * @return Cache\PConfigCache
	 */
	function getCache();
}
