<?php

namespace Friendica\Test\src\Core;

use Dice\Dice;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Config\PreloadConfiguration;
use Friendica\Core\Hook;
use Friendica\Core\L10n\L10n;
use Friendica\Core\Session\ISession;
use Friendica\Registry\DI;
use Friendica\Repository\Storage;
use Friendica\Database\Database;
use Friendica\Factory\ConfigFactory;
use Friendica\Model\Config\Config;
use Friendica\Model\Storage as S;
use Friendica\Core\Session;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Friendica\Test\Util\SampleStorageBackend;

class StorageTest extends DatabaseTest
{
	/** @var Database */
	private $dba;
	/** @var IConfiguration */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var L10n */
	private $l10n;

	use VFSTrait;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->logger = new NullLogger();

		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader        = new ConfigFileLoader($this->root->url());
		$configCache   = $configFactory->createCache($loader);

		$this->dba = new StaticDatabase($configCache, $profiler, $this->logger);

		$configModel  = new Config($this->dba);
		$this->config = new PreloadConfiguration($configCache, $configModel);

		$this->l10n = \Mockery::mock(L10n::class);
	}

	/**
	 * Test plain instancing first
	 */
	public function testInstance()
	{
		$storage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertInstanceOf(Storage::class, $storage);
	}

	public function dataStorages()
	{
		return [
			'empty'          => [
				'name'        => '',
				'assert'      => null,
				'assertName'  => '',
				'userBackend' => false,
			],
			'database'       => [
				'name'        => S\Database::NAME,
				'assert'      => S\Database::class,
				'assertName'  => S\Database::NAME,
				'userBackend' => true,
			],
			'filesystem'     => [
				'name'        => S\Filesystem::NAME,
				'assert'      => S\Filesystem::class,
				'assertName'  => S\Filesystem::NAME,
				'userBackend' => true,
			],
			'systemresource' => [
				'name'        => S\SystemResource::NAME,
				'assert'      => S\SystemResource::class,
				'assertName'  => S\SystemResource::NAME,
				// false here, because SystemResource isn't meant to be a user backend,
				// it's for system resources only
				'userBackend' => false,
			],
			'invalid'        => [
				'name'        => 'invalid',
				'assert'      => null,
				'assertName'  => '',
				'userBackend' => false,
			],
		];
	}

	/**
	 * Test the getByName() method
	 *
	 * @dataProvider dataStorages
	 */
	public function testGetByName($name, $assert, $assertName, $userBackend)
	{
		$storage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);

		$storage = $storage->selectFirst(['name' => $name, 'userBackend' => $userBackend]);

		if (!empty($assert)) {
			$this->assertInstanceOf(S\IStorage::class, $storage);
			$this->assertInstanceOf($assert, $storage);
			$this->assertEquals($name, $storage::getName());
		} else {
			$this->assertNull($storage);
		}
		$this->assertEquals($assertName, $storage);
	}

	/**
	 * Test the isValidBackend() method
	 *
	 * @dataProvider dataStorages
	 */
	public function testIsValidBackend($name, $assert, $assertName, $userBackend)
	{
		$storage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertEquals($userBackend, $storage->isValidBackend($name));
	}

	/**
	 * Test the method listBackends() with default setting
	 */
	public function testListBackends()
	{
		$storage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertEquals(Storage::DEFAULT_BACKENDS, $storage->listBackends());
	}

	/**
	 * Test the method getBackend()
	 *
	 * @dataProvider dataStorages
	 */
	public function testGetBackend($name, $assert, $assertName, $userBackend)
	{
		$storage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertNull($storage->getBackend());

		if ($userBackend) {
			$storage->setBackend($name);

			$this->assertInstanceOf($assert, $storage->getBackend());
		}
	}

	/**
	 * Test the method getBackend() with a pre-configured backend
	 *
	 * @dataProvider dataStorages
	 */
	public function testPresetBackend($name, $assert, $assertName, $userBackend)
	{
		$this->config->set('storage', 'name', $name);

		$storage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);

		if ($userBackend) {
			$this->assertInstanceOf($assert, $storage->getBackend());
		} else {
			$this->assertNull($storage->getBackend());
		}
	}

	/**
	 * Tests the register and unregister methods for a new backend storage class
	 *
	 * Uses a sample storage for testing
	 *
	 * @see SampleStorageBackend
	 */
	public function testRegisterUnregisterBackends()
	{
		/// @todo Remove dice once "Hook" is dynamic and mockable
		$dice   = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(ISession::class, ['instanceOf' => Session\Memory::class, 'shared' => true, 'call' => null]);
		DI::init($dice);

		$storage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);

		$this->assertTrue($storage->register(SampleStorageBackend::class));

		$this->assertEquals(array_merge(Storage::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName() => SampleStorageBackend::class,
		]), $storage->listBackends());
		$this->assertEquals(array_merge(Storage::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName() => SampleStorageBackend::class,
		]), $this->config->get('storage', 'backends'));

		// inline call to register own class as hook (testing purpose only)
		SampleStorageBackend::registerHook();
		Hook::loadHooks();

		$this->assertTrue($storage->setBackend(SampleStorageBackend::NAME));
		$this->assertEquals(SampleStorageBackend::NAME, $this->config->get('storage', 'name'));

		$this->assertInstanceOf(SampleStorageBackend::class, $storage->getBackend());

		$this->assertTrue($storage->unregister(SampleStorageBackend::class));
		$this->assertEquals(Storage::DEFAULT_BACKENDS, $this->config->get('storage', 'backends'));
		$this->assertEquals(Storage::DEFAULT_BACKENDS, $storage->listBackends());

		$this->assertNull($storage->getBackend());
		$this->assertNull($this->config->get('storage', 'name'));
	}

	/**
	 * Test moving data to a new storage (currently testing db & filesystem)
	 *
	 * @dataProvider dataStorages
	 */
	public function testMoveStorage($name, $assert, $assertName, $userBackend)
	{
		if (!$userBackend) {
			return;
		}

		$this->loadFixture(__DIR__ . '/../../datasets/storage/database.fixture.php', $this->dba);

		$repoStorage = new Storage($this->dba, $this->config, $this->logger, $this->l10n);
		$storage = $repoStorage->selectFirst(['name' => $name]);
		$repoStorage->move($storage);

		$photos = $this->dba->select('photo', ['backend-ref', 'backend-class', 'id', 'data']);

		while ($photo = $this->dba->fetch($photos)) {

			$this->assertEmpty($photo['data']);

			$storage = $repoStorage->selectFirst(['name' => $photo['backend-class']]);
			$data = $storage->get($photo['backend-ref']);

			$this->assertNotEmpty($data);
		}
	}
}
