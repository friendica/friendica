<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\Capability\ISetConfigValuesTransactionally;
use Friendica\Core\Config\Model\DatabaseConfig;
use Friendica\Core\Config\Model\ConfigTransaction;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;
use Friendica\Test\FixtureTestCase;
use Mockery\Exception\InvalidCountException;

class ConfigTransactionTest extends FixtureTestCase
{
	/** @var ConfigFileManager */
	protected $configFileManager;

	protected function setUp(): void
	{
		parent::setUp();

		$this->configFileManager = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			$this->root->url() . '/config',
			$this->root->url() . '/static'
		);
	}

	public function dataTests(): array
	{
		return [
			'default' => [
				'data' => include dirname(__FILE__, 4) . '/datasets/B.node.config.php',
			]
		];
	}

	public function testInstance()
	{
		$config            = new DatabaseConfig($this->dice->create(Database::class), new Cache());
		$configTransaction = new ConfigTransaction($config);

		self::assertInstanceOf(ISetConfigValuesTransactionally::class, $configTransaction);
		self::assertInstanceOf(ConfigTransaction::class, $configTransaction);
	}

	public function testConfigTransaction()
	{
		$config = new DatabaseConfig($this->dice->create(Database::class), new Cache());
		$config->set('config', 'key1', 'value1');
		$config->set('system', 'key2', 'value2');
		$config->set('system', 'keyDel', 'valueDel');
		$config->set('delete', 'keyDel', 'catDel');

		$configTransaction = new ConfigTransaction($config);

		// new key-value
		$configTransaction->set('transaction', 'key3', 'value3');
		// overwrite key-value
		$configTransaction->set('config', 'key1', 'changedValue1');
		// delete key-value
		$configTransaction->delete('system', 'keyDel');
		// delete last key of category - so the category is gone
		$configTransaction->delete('delete', 'keyDel');

		// The main config still doesn't know about the change
		self::assertNull($config->get('transaction', 'key3'));
		self::assertEquals('value1', $config->get('config', 'key1'));
		self::assertEquals('valueDel', $config->get('system', 'keyDel'));
		self::assertEquals('catDel', $config->get('delete', 'keyDel'));
		// The config file still doesn't know it either

		// save it back!
		$configTransaction->commit();

		// Now every config and file knows the change
		self::assertEquals('changedValue1', $config->get('config', 'key1'));
		self::assertEquals('value3', $config->get('transaction', 'key3'));
		self::assertNull($config->get('system', 'keyDel'));
		self::assertNull($config->get('delete', 'keyDel'));
		// the whole category should be gone
		self::assertNull($tempData['delete'] ?? null);
	}

	/**
	 * This test asserts that in empty transactions, no saveData is called, thus no config file writing was performed
	 */
	public function testNothingToDo()
	{
		$this->configFileManager = \Mockery::spy(ConfigFileManager::class);

		$config            = new DatabaseConfig($this->dice->create(Database::class), new Cache());
		$configTransaction = new ConfigTransaction($config);

		// commit empty transaction
		$configTransaction->commit();

		try {
			$this->configFileManager->shouldNotHaveReceived('saveData');
		} catch (InvalidCountException $exception) {
			self::fail($exception);
		}

		// If not failed, the test ends successfully :)
		self::assertTrue(true);
	}
}
