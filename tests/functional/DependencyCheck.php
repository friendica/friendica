<?php

namespace functional;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Configuration;
use Friendica\Database\Database;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\BasePath;
use Friendica\Util\Config\ConfigFileLoader;
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

		$this->dice = new Dice();
		$this->dice = $this->dice->addRules(include __DIR__ . '/../../static/dependencies.conf.php');
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
		/** @var Configuration $config */
		$config = $this->dice->create(Configuration::class);

		$this->assertInstanceOf(Configuration::class, $config);

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
		/** @var LoggerInterface $logger */
		$logger = $this->dice->create('$devLogger', ['dev']);

		self::assertInstanceOf(LoggerInterface::class, $logger);
	}
}
