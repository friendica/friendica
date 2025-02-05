<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public function createConfigFileManager(string $basePath, string $addonPath, array $server = []): Util\ConfigFileManager
	{
		if (!empty($server[self::CONFIG_DIR_ENV]) && is_dir($server[self::CONFIG_DIR_ENV])) {
			$configDir = $server[self::CONFIG_DIR_ENV];
		} else {
			$configDir = $basePath . DIRECTORY_SEPARATOR . self::CONFIG_DIR;
		}
		$staticDir = $basePath . DIRECTORY_SEPARATOR . self::STATIC_DIR;

		return new Util\ConfigFileManager($basePath, $addonPath, $configDir, $staticDir, $server);
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
