<?php

namespace Friendica\Core\Config\Cache;

/**
 * The interface for a system-wide ConfigCache
 */
interface IConfigCache
{
	/**
	 * Tries to load the specified configuration array into the config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * @param array $config
	 * @param bool  $overwrite Force value overwrite if the config key already exists
	 */
	function load(array $config, $overwrite = false);

	/**
	 * Gets a value from the config cache.
	 *
	 * @param string $cat     Config category
	 * @param string $key     Config key
	 *
	 * @return null|mixed Returns the value of the Config entry or null if not set
	 */
	function get($cat, $key = null);

	/**
	 * Sets a value in the config cache. Accepts raw output from the config table
	 *
	 * @param string $cat   Config category
	 * @param string $key   Config key
	 * @param mixed  $value Value to set
	 *
	 * @return bool True, if the value is set
	 */
	function set($cat, $key, $value);

	/**
	 * Deletes a value from the config cache.
	 *
	 * @param string $cat  Config category
	 * @param string $key  Config key
	 *
	 * @return bool true, if deleted
	 */
	function delete($cat, $key);

	/**
	 * Returns the whole configuration cache
	 *
	 * @return array
	 */
	function getAll();

	/**
	 * Combine the current config cache with a given config array and return the result
	 *
	 * @param array $config
	 * @param bool  $overwrite   If true, the given config cache overrules this config cache
	 *
	 * @return IConfigCache
	 */
	function combine(array $config, $overwrite = false);
}
