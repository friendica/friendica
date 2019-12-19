<?php

namespace Friendica\Core\Config;

/**
 * Interface for accessing system wide configurations
 */
interface IConfiguration
{

	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * All configuration values of the system are stored in the cache ( @see ConfigCache )
	 *
	 * @param string $cat The category of the configuration value
	 *
	 * @return void
	 */
	function load(string $cat = 'config');

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($cat) and a $key.
	 *
	 * Get a particular config value from the given category ($cat)
	 * and the $key from a cached storage either from the $this->configAdapter
	 * (@see IConfigAdapter) or from the $this->configCache (@see ConfigCache).
	 *
	 * @param string  $cat        The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	function get(string $cat, string $key, $default_value = null, bool $refresh = false);

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
	function set(string $cat, string $key, $value);

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in $this->configCache
	 * (@see ConfigCache) and removes it from the database (@see IConfigAdapter).
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return bool
	 */
	function delete(string $cat, string $key);

	/**
	 * Returns the Config Cache
	 *
	 * @return Cache\ConfigCache
	 */
	function getCache();
}
