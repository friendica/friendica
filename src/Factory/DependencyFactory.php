<?php

namespace Friendica\Factory;

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Factory;
use Friendica\Util\BasePath;
use Friendica\Util\Config;

class DependencyFactory
{
	/**
	 * Setting all default-dependencies of a friendica execution
	 *
	 * @param string $channel   The channel of this execution
	 * @param string $directory The base directory
	 * @param bool   $isBackend True, if it's a backend execution, otherwise false (Default true)
	 *
	 * @return App The application
	 *
	 * @throws \Exception
	 */
	public static function setUp($channel, $directory, $isBackend = true)
	{
		$basePath = BasePath::create($directory, $_SERVER);
		$mode = new App\Mode($basePath);
		$configLoader = new Config\ConfigFileLoader($basePath, $mode);
		$configCache = Factory\ConfigFactory::createCache($configLoader);
		$profiler = Factory\ProfilerFactory::create($configCache);
		Factory\DBFactory::init($configCache, $profiler, $_SERVER);
		$config = Factory\ConfigFactory::createConfig($configCache);
		// needed to call PConfig::init()
		Factory\ConfigFactory::createPConfig($configCache);
		$logger = Factory\LoggerFactory::create($channel, $config, $profiler);
		DBA::setLogger($logger);
		Factory\LoggerFactory::createDev($channel, $config, $profiler);

		return new App($config, $mode, $logger, $profiler, $isBackend);
	}
}
