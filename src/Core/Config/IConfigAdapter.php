<?php

namespace Friendica\Core\Config;

/**
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
interface IConfigAdapter
{
	/**
	 * @brief Loads all configuration values into a cached storage.
	 *
	 * All configuration values of the system are stored in global cache
	 * which is available under the global variable $a->config
	 *
	 * @param string  $cat The category of the configuration values to load
	 *
	 * @return void
	 */
	public function load($cat = "config");

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
	 * @param string  $cat           The category of the configuration value
	 * @param string  $k             The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public function get($cat, $k, $default_value = null, $refresh = false);

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
	public function set($cat, $k, $value);

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in $a->config
	 * and removes it from the database.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $k   The configuration key to delete
	 *
	 * @return mixed
	 */
	public function delete($cat, $k);
}
