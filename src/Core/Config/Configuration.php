<?php

namespace Friendica\Core\Config;

/**
 * This class is responsible for all system-wide configuration values in Friendica
 * There are two types of storage
 * - The Config-Files    (loaded into the FileCache @see Cache\IConfigCache )
 * - The Config-DB-Table (per Config-DB-adapter @see Adapter\IConfigAdapter )
 */
class Configuration
{
	/**
	 * The blacklist of configuration settings, which should not get saved to the backend
	 * @var array
	 */
	private $configSaveBlacklist = [
		'config' => [
			'hostname' => true,
		]
	];

	/**
	 * @var Cache\IConfigCache
	 */
	private $configCache;

	/**
	 * @var Adapter\IConfigAdapter
	 */
	private $configAdapter;

	/**
	 * @param Cache\IConfigCache     $configCache   The configuration cache (based on the config-files)
	 * @param Adapter\IConfigAdapter $configAdapter The configuration DB-backend
	 */
	public function __construct(Cache\IConfigCache $configCache, Adapter\IConfigAdapter $configAdapter)
	{
		$this->configCache = $configCache;
		$this->configAdapter = $configAdapter;

		$this->load();
	}

	/**
	 * Returns the Config Cache
	 *
	 * @return Cache\IConfigCache
	 */
	public function getCache()
	{
		return $this->configCache;
	}

	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * All configuration values of the system are stored in the cache ( @see IConfigCache )
	 *
	 * @param string $cat The category of the configuration value
	 *
	 * @return void
	 */
	public function load($cat = 'config')
	{
		// If not connected, do nothing
		if (!$this->configAdapter->isConnected()) {
			return;
		}

		// load the whole category out of the DB into the cache
		$this->configCache->load($this->configAdapter->load($cat), true);
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($cat) and a $key.
	 *
	 * Get a particular config value from the given category ($cat)
	 * and the $key from a cached storage either from the $this->configAdapter
	 * (@see IConfigAdapter ) or from the $this->configCache (@see IConfigCache ).
	 *
	 * @param string  $cat        The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public function get($cat, $key, $default_value = null, $refresh = false)
	{
		// if the value isn't loaded or refresh is needed, load it to the cache
		if ($this->configAdapter->isConnected() &&
			(!$this->configAdapter->isLoaded($cat, $key) ||
			$refresh)) {
			$dbvalue = $this->configAdapter->get($cat, $key);

			if ($dbvalue !== '!<unset>!') {
				$this->configCache->set($cat, $key, $dbvalue);
				return $dbvalue;
			}
		}

		// use the config cache for return
		if ($this->configCache->has($cat, $key)) {
			return $this->configCache->get($cat, $key);
		} else {
			return $default_value;
		}
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
	public function set($cat, $key, $value)
	{
		// set the cache first
		$cached = $this->configCache->set($cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configAdapter->isConnected() && empty($this->configSaveBlacklist[$cat][$key])) {
			return $cached;
		}

		$stored = $this->configAdapter->set($cat, $key, $value);

		return $cached && $stored;
	}

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in $this->configCache
	 * (@see ConfigCache ) and removes it from the database (@see IConfigAdapter ).
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return bool
	 */
	public function delete($cat, $key)
	{
		$cacheRemoved = $this->configCache->delete($cat, $key);

		if (!$this->configAdapter->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configAdapter->delete($cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
