<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

use Friendica\Core\Config\Util;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * The config factory for creating either the cache or the whole model
 */
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
	 * @return Util\ConfigFileManager
	 */
	public function createConfigFileManager(string $basePath, array $server = []): Util\ConfigFileManager
	{
		if (!empty($server[self::CONFIG_DIR_ENV]) && is_dir($server[self::CONFIG_DIR_ENV])) {
			$configDir = $server[self::CONFIG_DIR_ENV];
		} else {
			$configDir = $basePath . DIRECTORY_SEPARATOR . self::CONFIG_DIR;
		}
		$staticDir = $basePath . DIRECTORY_SEPARATOR . self::STATIC_DIR;

		return new Util\ConfigFileManager($basePath, $configDir, $staticDir, $server);
	}

	/**
	 * @param Util\ConfigFileManager $configFileManager The Config Cache manager (INI/config/.htconfig)
	 *
	 * @return Cache
	 */
	public function createCache(Util\ConfigFileManager $configFileManager): Cache
	{
		$configCache = new Cache();
		$configFileManager->setupCache($configCache);

		return $configCache;
	}
}
