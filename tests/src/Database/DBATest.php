<?php
namespace Friendica\Test\src\Database;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n\L10n;
use Friendica\Database\DBA;
use Friendica\Factory;
use Friendica\Test\DatabaseTest;
use Friendica\Util\BasePath;
use Friendica\Util\BaseURL;
use Friendica\Util\Config\ConfigFileLoader;

class DBATest extends DatabaseTest
{
	public function setUp()
	{
		$basePath = BasePath::create(dirname(__DIR__) . '/../../');
		$mode = new App\Mode($basePath);
		$router = new App\Router();
		$configLoader = new ConfigFileLoader($basePath, $mode);
		$configCache = Factory\ConfigFactory::createCache($configLoader);
		$profiler = Factory\ProfilerFactory::create($configCache);
		$database = Factory\DBFactory::init($configCache, $profiler, $_SERVER);
		$config = Factory\ConfigFactory::createConfig($configCache);
		Factory\ConfigFactory::createPConfig($configCache, new Config\Cache\PConfigCache());
		$logger = Factory\LoggerFactory::create('test', $database, $config, $profiler);
		$baseUrl = new BaseURL($config, $_SERVER);
		$l10n = new L10n(L10n::detectLanguage($config->get('system', 'language', 'en')),
			$database,
			$logger);

		$this->app = new App($database, $config, $mode, $router, $baseUrl, $logger, $profiler, $l10n, false);

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
