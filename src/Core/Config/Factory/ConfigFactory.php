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

namespace Friendica\Core\Config\Factory;

use Exception;
use Friendica\Core\Config;
use Friendica\Core\Config\Cache\Cache;
use Friendica\Core\Config\Model\Config as ConfigModel;
use Friendica\Core\Config\Cache\ConfigFileLoader;

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
	 * @return \Friendica\Core\Config\Cache\ConfigFileLoader
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
	 * @param \Friendica\Core\Config\Cache\ConfigFileLoader $loader The Config Cache loader (INI/config/.htconfig)
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
	 * @param \Friendica\Core\Config\Cache\Cache $configCache The config cache of this adapter
	 * @param ConfigModel                        $configModel The configuration model
	 *
	 * @return Config\IConfig
	 */
	public function create(Cache $configCache, ConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new Config\Type\PreloadConfig($configCache, $configModel);
		} else {
			$configuration = new Config\Type\JitConfig($configCache, $configModel);
		}


		return $configuration;
	}
}
