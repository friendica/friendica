<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\functional;

use Friendica\App;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Database\Database;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\BasePath;
use Friendica\Core\Config\Util\ConfigFileManager;
use Psr\Log\LoggerInterface;

class DependencyCheckTest extends FixtureTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		/** @var IManageConfigValues $config */
		$config = $this->dice->create(IManageConfigValues::class);
		$config->set('system', 'logfile', $this->root->url() . '/logs/friendica.log');
	}

	/**
	 * Test the creation of the BasePath
	 */
	public function testBasePath()
	{
		/** @var BasePath $basePath */
		$basePath = $this->dice->create(BasePath::class, [$this->root->url()]);

		self::assertInstanceOf(BasePath::class, $basePath);
		self::assertEquals($this->root->url(), $basePath->getPath());

		/** @var Database $dba */
		$dba = $this->dice->create(Database::class);
	}

	/**
	 * Test the initial config cache
	 * Should not need any other files
	 */
	public function testConfigFileLoader()
	{
		/** @var ConfigFileManager $configFileManager */
		$configFileManager = $this->dice->create(ConfigFileManager::class);

		self::assertInstanceOf(ConfigFileManager::class, $configFileManager);

		$configCache = new Cache();
		$configFileManager->setupCache($configCache);

		self::assertNotEmpty($configCache->getAll());
		self::assertArrayHasKey('database', $configCache->getAll());
		self::assertArrayHasKey('system', $configCache->getAll());
	}

	public function testDatabase()
	{
		/** @var Database $database */
		$database = $this->dice->create(Database::class);

		self::assertInstanceOf(Database::class, $database);
		self::assertContains($database->getDriver(), [Database::PDO, Database::MYSQLI], 'The driver returns an unexpected value');
		self::assertNotNull($database->getConnection(), 'There is no database connection');

		$result = $database->p("SELECT 1");
		self::assertEquals('', $database->errorMessage(), 'There had been a database error message');
		self::assertEquals(0, $database->errorNo(), 'There had been a database error number');

		self::assertTrue($database->connected(), 'The database is not connected');
	}

	public function testAppMode()
	{
		/** @var App\Mode $mode */
		$mode = $this->dice->create(App\Mode::class);

		self::assertInstanceOf(App\Mode::class, $mode);

		self::assertTrue($mode->has(App\Mode::LOCALCONFIGPRESENT), 'No local config present');
		self::assertTrue($mode->has(App\Mode::DBAVAILABLE), 'Database is not available');
		self::assertTrue($mode->has(App\Mode::MAINTENANCEDISABLED), 'In maintenance mode');

		self::assertTrue($mode->isNormal(), 'Not in normal mode');
	}

	public function testConfiguration()
	{
		/** @var IManageConfigValues $config */
		$config = $this->dice->create(IManageConfigValues::class);

		self::assertInstanceOf(IManageConfigValues::class, $config);

		self::assertNotEmpty($config->get('database', 'username'));
	}

	public function testLogger()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->dice->create(LoggerInterface::class, [['$channel' => 'test']]);

		self::assertInstanceOf(LoggerInterface::class, $logger);
	}

	public function testCache()
	{
		/** @var ICanCache $cache */
		$cache = $this->dice->create(ICanCache::class);


		self::assertInstanceOf(ICanCache::class, $cache);
	}

	public function testMemoryCache()
	{
		/** @var ICanCacheInMemory $cache */
		$cache = $this->dice->create(ICanCacheInMemory::class);

		// We need to check "just" ICache, because the default Cache is DB-Cache, which isn't a memorycache
		self::assertInstanceOf(ICanCache::class, $cache);
	}

	public function testLock()
	{
		/** @var ICanLock $cache */
		$lock = $this->dice->create(ICanLock::class);

		self::assertInstanceOf(ICanLock::class, $lock);
	}
}
