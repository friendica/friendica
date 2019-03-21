<?php
namespace Friendica\Test\Database;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Config\Cache;
use Friendica\Database\DBA;
use Friendica\Factory;
use Friendica\Test\DatabaseTest;
use Friendica\Util\BasePath;

class DBATest extends DatabaseTest
{
	public function setUp()
	{
		$basePath = BasePath::create(dirname(__DIR__) . '/../../');
		$mode = new App\Mode($basePath);
		$configLoader = new Cache\ConfigCacheLoader($basePath, $mode);
		$configCache = Factory\ConfigFactory::createCache($configLoader);
		$profiler = Factory\ProfilerFactory::create($configCache);
		Factory\DBFactory::init($configCache, $profiler, $_SERVER);
		$config = Factory\ConfigFactory::createConfig($configCache);
		Factory\ConfigFactory::createPConfig($configCache);
		$logger = Factory\LoggerFactory::create('test', $config, $profiler);
		$this->app = new App($config, $mode, $logger, $profiler, false);

		parent::setUp();

		// Default config
		Config::set('config', 'hostname', 'localhost');
		Config::set('system', 'throttle_limit_day', 100);
		Config::set('system', 'throttle_limit_week', 100);
		Config::set('system', 'throttle_limit_month', 100);
		Config::set('system', 'theme', 'system_theme');
	}

	/**
	 * @small
	 */
	public function testExists() {

		$this->assertTrue(DBA::exists('config', []));
		$this->assertFalse(DBA::exists('notable', []));

		$this->assertTrue(DBA::exists('config', null));
		$this->assertFalse(DBA::exists('notable', null));

		$this->assertTrue(DBA::exists('config', ['k' => 'hostname']));
		$this->assertFalse(DBA::exists('config', ['k' => 'nonsense']));
	}
}
