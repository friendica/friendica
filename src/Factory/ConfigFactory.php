<?php

namespace Friendica\Factory;

use Friendica\Core;
use Friendica\Core\Config;
use Friendica\Core\Config\Adapter;
use Friendica\Core\Config\Cache;

class ConfigFactory
{
	/**
	 * @param Cache\ConfigCacheManager $configCacheManager The Config Cache loader (INI/config/.htconfig)
	 *
	 * @return Cache\ConfigCache
	 */
	public static function createCache(Cache\ConfigCacheManager $configCacheManager)
	{
		$configCache = new Cache\ConfigCache();
		$configCacheManager->loadConfigFiles($configCache);

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
	 * @param int                $uid         The UID of the current user
	 *
	 * @return Config\PConfiguration
	 */
	public static function createPConfig(Cache\ConfigCache $configCache, $uid = null)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadPConfigAdapter($uid);
		} else {
			$configAdapter = new Adapter\JITPConfigAdapter();
		}

		$configuration = new Config\PConfiguration($configCache, $configAdapter);

		// Set the config in the static container for legacy usage
		Core\PConfig::init($configuration);

		return $configuration;
	}
}
