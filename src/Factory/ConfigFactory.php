<?php

namespace Friendica\Factory;

use Friendica\Core;
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
	public static function createCache(ConfigFileLoader $loader)
	{
		$configCache = new Cache\ConfigCache();
		$loader->setupCache($configCache);

		return $configCache;
	}

	/**
	 * @param Cache\ConfigCache $configCache The config cache of this adapter
	 * @param Database          $dba         The database connection
	 *
	 * @return Config\Configuration
	 */
	public static function createConfig(Cache\ConfigCache $configCache, Database $dba)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadConfigAdapter($dba);
		} else {
			$configAdapter = new Adapter\JITConfigAdapter($dba);
		}

		$configuration = new Config\Configuration($configCache, $configAdapter);

		// Set the config in the static container for legacy usage
		Core\Config::init($configuration);

		return $configuration;
	}

	/**
	 * @param Cache\ConfigCache $configCache The config cache of this adapter
	 * @param Database          $dba         The database connection
	 * @param int               $uid         The UID of the current user
	 *
	 * @return Config\PConfiguration
	 */
	public static function createPConfig(Cache\ConfigCache $configCache, Database $dba, $uid = null)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadPConfigAdapter($dba, $uid);
		} else {
			$configAdapter = new Adapter\JITPConfigAdapter($dba);
		}

		$configuration = new Config\PConfiguration($configCache, $configAdapter);

		// Set the config in the static container for legacy usage
		Core\PConfig::init($configuration);

		return $configuration;
	}
}
