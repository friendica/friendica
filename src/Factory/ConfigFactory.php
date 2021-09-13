<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Factory;

use Exception;
use Friendica\Core\Config;
use Friendica\Core\Config\Cache;
use Friendica\Model\Config\Config as ConfigModel;
use Friendica\Model\Config\PConfig as PConfigModel;
use Friendica\Util\ConfigFileLoader;

class ConfigFactory
{
	/**
	 * The key of the $_SERVER variable to override the config directory
	 *
	 * @var string
	 */
	const CONFIG_DIR_ENV = 'FRIENDICA_CONFIG_DIR';

	/**
	 * The Sub directory of the config-files
	 *
	 * @var string
	 */
	const CONFIG_DIR = 'config';

	/**
	 * The Sub directory of the static config-files
	 *
	 * @var string
	 */
	const STATIC_DIR = 'static';

	/**
	 * @param string $basePath The basepath of FRIENDICA
	 * @param array $serer the $_SERVER array
	 *
	 * @return ConfigFileLoader
	 */
	public function createConfigFileLoader(string $basePath, array $server = [])
	{
		if (!empty($server[self::CONFIG_DIR_ENV]) && is_dir($server[self::CONFIG_DIR_ENV])) {
			$configDir = $server[self::CONFIG_DIR_ENV];
		} else {
			$configDir = $basePath . DIRECTORY_SEPARATOR . self::CONFIG_DIR;
		}
		$staticDir = $basePath . DIRECTORY_SEPARATOR . self::STATIC_DIR;

		return new ConfigFileLoader($basePath, $configDir, $staticDir);
	}

	/**
	 * @param ConfigFileLoader $loader The Config Cache loader (INI/config/.htconfig)
	 *
	 * @return Cache
	 *
	 * @throws Exception
	 */
	public function createCache(ConfigFileLoader $loader, array $server = [])
	{
		$configCache = new Cache();
		$loader->setupCache($configCache, $server);

		return $configCache;
	}

	/**
	 * @param Cache       $configCache The config cache of this adapter
	 * @param ConfigModel $configModel The configuration model
	 *
	 * @return Config\IConfig
	 */
	public function createConfig(Cache $configCache, ConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new Config\PreloadConfig($configCache, $configModel);
		} else {
			$configuration = new Config\JitConfig($configCache, $configModel);
		}


		return $configuration;
	}

	/**
	 * @param Cache                         $configCache  The config cache
	 * @param \Friendica\Core\PConfig\Cache $pConfigCache The personal config cache
	 * @param PConfigModel                  $configModel  The configuration model
	 *
	 * @return \Friendica\Core\PConfig\IPConfig
	 */
	public function createPConfig(Cache $configCache, \Friendica\Core\PConfig\Cache $pConfigCache, PConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new \Friendica\Core\PConfig\PreloadPConfig($pConfigCache, $configModel);
		} else {
			$configuration = new \Friendica\Core\PConfig\JitPConfig($pConfigCache, $configModel);
		}

		return $configuration;
	}
}
