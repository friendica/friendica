<?php

namespace Friendica\Factory;

use Friendica\Core\Config;
use Friendica\Core\Config\Adapter;
use Friendica\Core\Config\Cache;
use Friendica\Database\Database;
use Friendica\Util\Config\ConfigFileLoader;

class ConfigFactory
{
	/**
	 * @param ConfigFileLoader $loader The Config Cache loader (INI/config/.htconfig)
	 *
	 * @return Cache\ConfigCache
	 */
	public function createCache(ConfigFileLoader $loader)
	{
		$configCache = new Cache\ConfigCache();
		$loader->setupCache($configCache);

		return $configCache;
	}

	/**
	 * @param Cache\ConfigCache $configCache The config cache of this adapter
	 * @param Database          $database    The database connection
	 *
	 * @return Config\Configuration
	 */
	public function createConfig(Cache\ConfigCache $configCache, Database $database)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadConfigAdapter($database);
		} else {
			$configAdapter = new Adapter\JITConfigAdapter($database);
		}

		$configuration = new Config\Configuration($configCache, $configAdapter);

		return $configuration;
	}

	/**
	 * @param Cache\ConfigCache $configCache The config cache of this adapter
	 * @param Database          $database    The database connection
	 * @param int               $uid         The UID of the current user
	 *
	 * @return Config\PConfiguration
	 */
	public function createPConfig(Cache\ConfigCache $configCache, Database $database, $uid = null)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadPConfigAdapter($database, $uid);
		} else {
			$configAdapter = new Adapter\JITPConfigAdapter($database);
		}

		$configuration = new Config\PConfiguration($configCache, $configAdapter);

		return $configuration;
	}
}
