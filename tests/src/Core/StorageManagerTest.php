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

namespace Friendica\Test\src\Core;

use Dice\Dice;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Config\PreloadConfig;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session\ISession;
use Friendica\Core\StorageManager;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Factory\ConfigFactory;
use Friendica\Model\Config\Config;
use Friendica\Model\Storage;
use Friendica\Core\Session;
use Friendica\Network\HTTPClient;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Friendica\Test\Util\SampleStorageBackend;

class StorageManagerTest extends DatabaseTest
{
	use VFSTrait;
	/** @var Database */
	private $dba;
	/** @var IConfig */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var L10n */
	private $l10n;
	/** @var HTTPClient */
	private $httpRequest;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->logger = new NullLogger();

		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('startRecording');
		$profiler->shouldReceive('stopRecording');
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader        = new ConfigFileLoader($this->root->url());
		$configCache   = $configFactory->createCache($loader);

		$this->dba = new StaticDatabase($configCache, $profiler, $this->logger);

		$configModel  = new Config($this->dba);
		$this->config = new PreloadConfig($configCache, $configModel);
		$this->config->set('storage', 'name', 'Database');

		$this->l10n = \Mockery::mock(L10n::class);

		$this->httpRequest = \Mockery::mock(HTTPClient::class);
	}

	/**
	 * Test plain instancing first
	 */
	public function testInstance()
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n, $this->httpRequest);

		self::assertInstanceOf(StorageManager::class, $storageManager);
	}

	public function dataStorages()
	{
		return [
			'empty' => [
				'name'       => '',
				'valid'      => false,
				'interface'  => Storage\IStorage::class,
				'assert'     => null,
				'assertName' => '',
			],
			'database' => [
				'name'       => Storage\Database::NAME,
				'valid'      => true,
				'interface'  => Storage\IWritableStorage::class,
				'assert'     => Storage\Database::class,
				'assertName' => Storage\Database::NAME,
			],
			'filesystem' => [
				'name'       => Storage\Filesystem::NAME,
				'valid'      => true,
				'interface'  => Storage\IWritableStorage::class,
				'assert'     => Storage\Filesystem::class,
				'assertName' => Storage\Filesystem::NAME,
			],
			'systemresource' => [
				'name'       => Storage\SystemResource::NAME,
				'valid'      => true,
				'interface'  => Storage\IStorage::class,
				'assert'     => Storage\SystemResource::class,
				'assertName' => Storage\SystemResource::NAME,
			],
			'invalid' => [
				'name'        => 'invalid',
				'valid'       => false,
				'interface'   => null,
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
	public function testGetByName($name, $valid, $interface, $assert, $assertName)
	{
		if (!$valid) {
			$this->expectException(Storage\InvalidClassStorageException::class);
		}

		if ($interface === Storage\IWritableStorage::class) {
			$this->config->set('storage', 'name', $name);
		}

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		if ($interface === Storage\IWritableStorage::class) {
			$storage = $storageManager->getWritableStorageByName($name);
		} else {
			$storage = $storageManager->getByName($name);
		}

		self::assertInstanceOf($interface, $storage);
		self::assertInstanceOf($assert, $storage);
		self::assertEquals($assertName, $storage);
	}

	/**
	 * Test the isValidBackend() method
	 *
	 * @dataProvider dataStorages
	 */
	public function testIsValidBackend($name, $valid, $interface, $assert, $assertName)
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		// true in every of the backends
		self::assertEquals(!empty($assertName), $storageManager->isValidBackend($name));

		// if it's a IWritableStorage, the valid backend should return true, otherwise false
		self::assertEquals($interface === Storage\IWritableStorage::class, $storageManager->isValidBackend($name, StorageManager::DEFAULT_BACKENDS));
	}

	/**
	 * Test the method listBackends() with default setting
	 */
	public function testListBackends()
	{
		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		self::assertEquals(StorageManager::DEFAULT_BACKENDS, $storageManager->listBackends());
	}

	/**
	 * Test the method getBackend()
	 *
	 * @dataProvider dataStorages
	 */
	public function testGetBackend($name, $valid, $interface, $assert, $assertName)
	{
		if ($interface !== Storage\IWritableStorage::class) {
			static::markTestSkipped('only works for IWritableStorage');
		}

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		$selBackend = $storageManager->getWritableStorageByName($name);
		$storageManager->setBackend($selBackend);

		self::assertInstanceOf($assert, $storageManager->getBackend());
	}

	/**
	 * Test the method getBackend() with a pre-configured backend
	 *
	 * @dataProvider dataStorages
	 */
	public function testPresetBackend($name, $valid, $interface, $assert, $assertName)
	{
		$this->config->set('storage', 'name', $name);
		if ($interface !== Storage\IWritableStorage::class) {
			$this->expectException(Storage\InvalidClassStorageException::class);
		}

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		self::assertInstanceOf($assert, $storageManager->getBackend());
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
		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(ISession::class, ['instanceOf' => Session\Memory::class, 'shared' => true, 'call' => null]);
		DI::init($dice);

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		self::assertTrue($storageManager->register(SampleStorageBackend::class));

		self::assertEquals(array_merge(StorageManager::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName(),
		]), $storageManager->listBackends());
		self::assertEquals(array_merge(StorageManager::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName()
		]), $this->config->get('storage', 'backends'));

		self::assertTrue($storageManager->unregister(SampleStorageBackend::class));
		self::assertEquals(StorageManager::DEFAULT_BACKENDS, $this->config->get('storage', 'backends'));
		self::assertEquals(StorageManager::DEFAULT_BACKENDS, $storageManager->listBackends());
	}

	/**
	 * tests that an active backend cannot get unregistered
	 */
	public function testUnregisterActiveBackend()
	{
		/// @todo Remove dice once "Hook" is dynamic and mockable
		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(ISession::class, ['instanceOf' => Session\Memory::class, 'shared' => true, 'call' => null]);
		DI::init($dice);

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);

		self::assertTrue($storageManager->register(SampleStorageBackend::class));

		self::assertEquals(array_merge(StorageManager::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName(),
		]), $storageManager->listBackends());
		self::assertEquals(array_merge(StorageManager::DEFAULT_BACKENDS, [
			SampleStorageBackend::getName()
		]), $this->config->get('storage', 'backends'));

		// inline call to register own class as hook (testing purpose only)
		SampleStorageBackend::registerHook();
		Hook::loadHooks();

		self::assertTrue($storageManager->setBackend($storageManager->getWritableStorageByName(SampleStorageBackend::NAME)));
		self::assertEquals(SampleStorageBackend::NAME, $this->config->get('storage', 'name'));

		self::assertInstanceOf(SampleStorageBackend::class, $storageManager->getBackend());

		self::expectException(Storage\StorageException::class);
		self::expectExceptionMessage('Cannot unregister Sample Storage, because it\'s currently active.');

		$storageManager->unregister(SampleStorageBackend::class);
	}

	/**
	 * Test moving data to a new storage (currently testing db & filesystem)
	 *
	 * @dataProvider dataStorages
	 */
	public function testMoveStorage($name, $valid, $interface, $assert, $assertName)
	{
		if ($interface !== Storage\IWritableStorage::class) {
			self::markTestSkipped("No user backend");
		}

		$this->loadFixture(__DIR__ . '/../../datasets/storage/database.fixture.php', $this->dba);

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);
		$storage        = $storageManager->getWritableStorageByName($name);
		$storageManager->move($storage);

		$photos = $this->dba->select('photo', ['backend-ref', 'backend-class', 'id', 'data']);

		while ($photo = $this->dba->fetch($photos)) {
			self::assertEmpty($photo['data']);

			$storage = $storageManager->getByName($photo['backend-class']);
			$data    = $storage->get($photo['backend-ref']);

			self::assertNotEmpty($data);
		}
	}

	/**
	 * Test moving data to a WRONG storage
	 */
	public function testWrongWritableStorage()
	{
		$this->expectException(Storage\InvalidClassStorageException::class);
		$this->expectExceptionMessage('Backend SystemResource is not valid');

		$storageManager = new StorageManager($this->dba, $this->config, $this->logger, $this->l10n);
		$storage        = $storageManager->getWritableStorageByName(Storage\SystemResource::getName());
		$storageManager->move($storage);
	}
}
