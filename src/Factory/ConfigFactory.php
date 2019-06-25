<?php

namespace Friendica\Factory;

use Friendica\Core;
use Friendica\Core\Config;
use Friendica\Core\Config\Adapter;
use Friendica\Core\Config\Cache;
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
	 *
	 * @return Config\Configuration
	 */
	public static function createConfig(Cache\ConfigCache $configCache)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadConfigAdapter();
		} else {
			$configAdapter = new Adapter\JITConfigAdapter();
		}

		$configuration = new Config\Configuration($configCache, $configAdapter);

		// Set the config in the static container for legacy usage
		Core\Config::init($configuration);

		return $configuration;
	}

	/**
	 * @param Cache\ConfigCache  $configCache The config cache of this adapter
	 *
	 * @return Config\PConfiguration
	 */
	public static function createPConfig(Cache\ConfigCache $configCache)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadPConfigAdapter();
		} else {
			$configAdapter = new Adapter\JITPConfigAdapter();
		}

		$configuration = new Config\PConfiguration($configCache, $configAdapter);

		// Set the config in the static container for legacy usage
		Core\PConfig::init($configuration);

		return $configuration;
	}
}
