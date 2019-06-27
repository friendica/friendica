<?php

namespace Friendica\Core\Config\Adapter;

/**
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
interface IConfigAdapter
{
	/**
	 * Loads all configuration values and returns the loaded category as an array.
	 *
	 * @param string $cat The category of the configuration values to load
	 *
	 * @return array
	 */
	public function load(string $cat = 'config');

	/**
	 * Get a particular system-wide config variable given the category name
	 * ($family) and a key.
	 *
	 * Note: Boolean variables are defined as 0/1 in the database
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to query
	 *
	 * @return null|mixed Stored value or null if it does not exist
	 */
	public function get(string $cat, string $key);

	/**
	 * Stores a config value ($value) in the category ($family) under the key ($key).
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 */
	public function set(string $cat, string $key, $value);

	/**
	 * Removes the configured value from the stored cache
	 * and removes it from the database.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool Operation success
	 */
	public function delete(string $cat, string $key);

	/**
	 * Checks, if the current adapter is connected to the backend
	 *
	 * @return bool
	 */
	public function isConnected();

	/**
	 * Checks, if a config key ($key) in the category ($cat) is already loaded.
	 *
	 * @param string $cat The configuration category
	 * @param string $key The configuration key
	 *
	 * @return bool
	 */
	public function isLoaded(string $cat, string $key);
}
