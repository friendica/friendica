<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\KeyValueStorage;

use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Core\KeyValueStorage\Type\DBKeyValueStorage;
use Friendica\Database\Database;
use Friendica\Test\MockedTestCase;
use Friendica\Test\Util\CreateDatabaseTrait;

class DBKeyValueStorageTest extends MockedTestCase
{
	use CreateDatabaseTrait;

	/** @var Database */
	protected $database;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->setUpDb();
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->tearDownDb();
	}

	public function getInstance(): IManageKeyValuePairs
	{
		$this->database = $this->getDbInstance();

		return new DBKeyValueStorage($this->database);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testUpdatedAt($k, $v): void
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$entry = $this->database->selectFirst(DBKeyValueStorage::DB_KEY_VALUE_TABLE, ['updated_at'], ['k' => $k]);
		self::assertNotEmpty($entry);

		$updateAt = $entry['updated_at'];

		$instance->set($k, 'another_value');

		self::assertEquals('another_value', $instance->get($k));
		self::assertEquals('another_value', $instance[$k]);

		$entry = $this->database->selectFirst(DBKeyValueStorage::DB_KEY_VALUE_TABLE, ['updated_at'], ['k' => $k]);
		self::assertNotEmpty($entry);

		$updateAtAfter = $entry['updated_at'];

		self::assertGreaterThanOrEqual($updateAt, $updateAtAfter);
	}

	public function testInstance(): void
	{
		$instance = $this->getInstance();

		self::assertInstanceOf(IManageKeyValuePairs::class, $instance); // @phpstan-ignore staticMethod.alreadyNarrowedType
	}

	public static function dataTests(): array
	{
		return [
			'string'       => ['k' => 'data', 'v' => 'it'],
			'boolTrue'     => ['k' => 'data', 'v' => true],
			'boolFalse'    => ['k' => 'data', 'v' => false],
			'integer'      => ['k' => 'data', 'v' => 235],
			'decimal'      => ['k' => 'data', 'v' => 2.456],
			'array'        => ['k' => 'data', 'v' => ['1', 2, '3', true, false]],
			'boolIntTrue'  => ['k' => 'data', 'v' => 1],
			'boolIntFalse' => ['k' => 'data', 'v' => 0],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testGetSetDelete($k, $v): void
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$instance->delete($k);

		self::assertNull($instance->get($k));
		self::assertNull($instance[$k]);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testSetOverride($k, $v): void
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$instance->set($k, 'another_value');

		self::assertEquals('another_value', $instance->get($k));
		self::assertEquals('another_value', $instance[$k]);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testOffsetSetDelete($k, $v): void
	{
		$instance = $this->getInstance();

		$instance[$k] = $v;

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		unset($instance[$k]);

		self::assertNull($instance->get($k));
		self::assertNull($instance[$k]);
	}
}
