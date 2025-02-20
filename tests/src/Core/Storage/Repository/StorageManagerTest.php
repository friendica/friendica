<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Storage\Repository;

use Dice\Dice;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Type\Memory;
use Friendica\Core\Storage\Exception\InvalidClassStorageException;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Repository\StorageManager;
use Friendica\Core\Storage\Type\Filesystem;
use Friendica\Core\Storage\Type\SystemResource;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Storage\Type;
use Friendica\Test\DatabaseTestCase;
use Friendica\Test\Util\CreateDatabaseTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Friendica\Test\Util\SampleStorageBackend;

class StorageManagerTest extends DatabaseTestCase
{
	use CreateDatabaseTrait;

	/** @var IManageConfigValues */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var L10n */
	private $l10n;

	/** @var Database */
	protected $database;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->setUpDb();

		vfsStream::newDirectory(Type\FilesystemConfig::DEFAULT_BASE_FOLDER, 0777)->at($this->root);

		$this->logger   = new NullLogger();
		$this->database = $this->getDbInstance();

		$configFactory     = new Config();
		$configFileManager = $configFactory->createConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
		);
		$configCache = $configFactory->createCache($configFileManager);

		$this->config = new \Friendica\Core\Config\Model\DatabaseConfig($this->database, $configCache);
		$this->config->set('storage', 'name', 'Database');
		$this->config->set('storage', 'filesystem_path', $this->root->getChild(Type\FilesystemConfig::DEFAULT_BASE_FOLDER)
																	->url());

		$this->l10n = \Mockery::mock(L10n::class);

	}

	protected function tearDown(): void
	{
		$this->root->removeChild(Type\FilesystemConfig::DEFAULT_BASE_FOLDER);

		parent::tearDown();
	}

	/**
	 * Test plain instancing first
	 */
	public function testInstance()
	{
		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

		self::assertInstanceOf(StorageManager::class, $storageManager);
	}

	public function dataStorages()
	{
		return [
			'empty' => [
				'name'       => '',
				'valid'      => false,
				'interface'  => ICanReadFromStorage::class,
				'assert'     => null,
				'assertName' => '',
			],
			'database' => [
				'name'       => Type\Database::NAME,
				'valid'      => true,
				'interface'  => ICanWriteToStorage::class,
				'assert'     => Type\Database::class,
				'assertName' => Type\Database::NAME,
			],
			'filesystem' => [
				'name'       => Filesystem::NAME,
				'valid'      => true,
				'interface'  => ICanWriteToStorage::class,
				'assert'     => Filesystem::class,
				'assertName' => Filesystem::NAME,
			],
			'systemresource' => [
				'name'       => SystemResource::NAME,
				'valid'      => true,
				'interface'  => ICanReadFromStorage::class,
				'assert'     => SystemResource::class,
				'assertName' => SystemResource::NAME,
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
			$this->expectException(InvalidClassStorageException::class);
		}

		if ($interface === ICanWriteToStorage::class) {
			$this->config->set('storage', 'name', $name);
		}

		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

		if ($interface === ICanWriteToStorage::class) {
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
		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

		// true in every of the backends
		self::assertEquals(!empty($assertName), $storageManager->isValidBackend($name));

		// if it's a ICanWriteToStorage, the valid backend should return true, otherwise false
		self::assertEquals($interface === ICanWriteToStorage::class, $storageManager->isValidBackend($name, StorageManager::DEFAULT_BACKENDS));
	}

	/**
	 * Test the method listBackends() with default setting
	 */
	public function testListBackends()
	{
		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

		self::assertEquals(StorageManager::DEFAULT_BACKENDS, $storageManager->listBackends());
	}

	/**
	 * Test the method getBackend()
	 *
	 * @dataProvider dataStorages
	 */
	public function testGetBackend($name, $valid, $interface, $assert, $assertName)
	{
		if ($interface !== ICanWriteToStorage::class) {
			static::markTestSkipped('only works for ICanWriteToStorage');
		}

		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

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
		if ($interface !== ICanWriteToStorage::class) {
			$this->expectException(InvalidClassStorageException::class);
		}

		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

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
			->addRules(include __DIR__ . '/../../../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(IHandleSessions::class, ['instanceOf' => Memory::class, 'shared' => true, 'call' => null]);
		DI::init($dice);

		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

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
			->addRules(include __DIR__ . '/../../../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(IHandleSessions::class, ['instanceOf' => Memory::class, 'shared' => true, 'call' => null]);
		DI::init($dice);

		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);

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

		self::expectException(StorageException::class);
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
		if ($interface !== ICanWriteToStorage::class) {
			self::markTestSkipped("No user backend");
		}

		$this->loadFixture(__DIR__ . '/../../../../datasets/storage/database.fixture.php', $this->database);

		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);
		$storage        = $storageManager->getWritableStorageByName($name);
		$storageManager->move($storage);

		$photos = $this->database->select('photo', ['backend-ref', 'backend-class', 'id', 'data']);

		while ($photo = $this->database->fetch($photos)) {
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
		$this->expectException(InvalidClassStorageException::class);
		$this->expectExceptionMessage('Backend SystemResource is not valid');

		$storageManager = new StorageManager($this->database, $this->config, $this->logger, $this->l10n, false);
		$storage        = $storageManager->getWritableStorageByName(SystemResource::getName());
		$storageManager->move($storage);
	}
}
