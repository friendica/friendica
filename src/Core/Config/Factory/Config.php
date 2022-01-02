<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\Core\Config\Capability;
use Friendica\Core\Config\Repository;
use Friendica\Core\Config\Type;
use Friendica\Core\Config\Util;
use Friendica\Core\Config\ValueObject\Cache;

class Config
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
	 * @param array  $server   The $_SERVER array
	 *
	 * @return Util\ConfigFileLoader
	 */
	public function createConfigFileLoader(string $basePath, array $server = []): Util\ConfigFileLoader
	{
		if (!empty($server[self::CONFIG_DIR_ENV]) && is_dir($server[self::CONFIG_DIR_ENV])) {
			$configDir = $server[self::CONFIG_DIR_ENV];
		} else {
			$configDir = $basePath . DIRECTORY_SEPARATOR . self::CONFIG_DIR;
		}
		$staticDir = $basePath . DIRECTORY_SEPARATOR . self::STATIC_DIR;

		return new Util\ConfigFileLoader($basePath, $configDir, $staticDir);
	}

	/**
	 * @param Util\ConfigFileLoader $loader The Config Cache loader (INI/config/.htconfig)
	 * @param array                 $server
	 *
	 * @return Cache
	 */
	public function createCache(Util\ConfigFileLoader $loader, array $server = []): Cache
	{
		$configCache = new Cache();
		$loader->setupCache($configCache, $server);

		return $configCache;
	}

	/**
	 * @param Cache $configCache The config cache of this adapter
	 * @param Repository\Config $configRepo  The configuration repository
	 *
	 * @return Capability\IManageConfigValues
	 */
	public function create(Cache $configCache, Repository\Config $configRepo)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new Type\PreloadConfig($configCache, $configRepo);
		} else {
			$configuration = new Type\JitConfig($configCache, $configRepo);
		}

		return $configuration;
	}
}
