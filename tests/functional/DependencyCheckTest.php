<?php

namespace functional;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Cache\IMemoryCache;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Lock\ILock;
use Friendica\Database\Database;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\BasePath;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class dependencyCheck extends TestCase
{
	use VFSTrait;

	/**
	 * @var Dice
	 */
	private $dice;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->dice = (new Dice())
			->addRules(include __DIR__ . '/../../static/dependencies.config.php');
	}

	/**
	 * Test the creation of the BasePath
	 */
	public function testBasePath()
	{
		/** @var BasePath $basePath */
		$basePath = $this->dice->create(BasePath::class, [$this->root->url()]);

		$this->assertInstanceOf(BasePath::class, $basePath);
		$this->assertEquals($this->root->url(), $basePath->getPath());
	}

	/**
	 * Test the initial config cache
	 * Should not need any other files
	 */
	public function testConfigFileLoader()
	{
		/** @var ConfigFileLoader $configFileLoader */
		$configFileLoader = $this->dice->create(ConfigFileLoader::class);

		$this->assertInstanceOf(ConfigFileLoader::class, $configFileLoader);

		$configCache = new ConfigCache();
		$configFileLoader->setupCache($configCache);

		$this->assertNotEmpty($configCache->getAll());
		$this->assertArrayHasKey('database', $configCache->getAll());
		$this->assertArrayHasKey('system', $configCache->getAll());
	}

	/**
	 * Test the construction of a profiler class with DI
	 */
	public function testProfiler()
	{
		/** @var Profiler $profiler */
		$profiler = $this->dice->create(Profiler::class);

		$this->assertInstanceOf(Profiler::class, $profiler);

		$configCache = new ConfigCache([
			'system' => [
				'profiler' => true,
			],
			'rendertime' => [
				'callstack' => true,
			]
		]);

		// create new DI-library because of shared instance rule (so the Profiler wouldn't get created twice)
		$this->dice = new Dice();
		$profiler = $this->dice->create(Profiler::class, [$configCache]);

		$this->assertInstanceOf(Profiler::class, $profiler);
		$this->assertTrue($profiler->isRendertime());
	}

	public function testDatabase()
	{
		/** @var Database $database */
		$database = $this->dice->create(Database::class);

		$this->assertInstanceOf(Database::class, $database);
		$this->assertTrue($database->connected());
	}

	public function testAppMode()
	{
		/** @var App\Mode $mode */
		$mode = $this->dice->create(App\Mode::class);

		$this->assertInstanceOf(App\Mode::class, $mode);

		$this->assertTrue($mode->isNormal());
	}

	public function testConfiguration()
	{
		/** @var IConfiguration $config */
		$config = $this->dice->create(IConfiguration::class);

		$this->assertInstanceOf(IConfiguration::class, $config);

		$this->assertNotEmpty($config->get('database', 'username'));
	}

	public function testLogger()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->dice->create(LoggerInterface::class, ['test']);

		$this->assertInstanceOf(LoggerInterface::class, $logger);
	}

	public function testDevLogger()
	{
		/** @var IConfiguration $config */
		$config = $this->dice->create(IConfiguration::class);
		$config->set('system', 'dlogfile', $this->root->url() . '/friendica.log');

		/** @var LoggerInterface $logger */
		$logger = $this->dice->create('$devLogger', ['dev']);

		$this->assertInstanceOf(LoggerInterface::class, $logger);
	}

	public function testCache()
	{
		/** @var ICache $cache */
		$cache = $this->dice->create(ICache::class);

		$this->assertInstanceOf(ICache::class, $cache);
	}

	public function testMemoryCache()
	{
		/** @var IMemoryCache $cache */
		$cache = $this->dice->create(IMemoryCache::class);

		// We need to check "just" ICache, because the default Cache is DB-Cache, which isn't a memorycache
		$this->assertInstanceOf(ICache::class, $cache);
	}

	public function testLock()
	{
		/** @var ILock $cache */
		$lock = $this->dice->create(ILock::class);

		$this->assertInstanceOf(ILock::class, $lock);
	}
}
