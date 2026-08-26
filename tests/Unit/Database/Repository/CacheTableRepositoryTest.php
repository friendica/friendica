<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Database\Repository;

use Friendica\Database\Database;
use Friendica\Database\DatabaseException;
use Friendica\Database\Repository\CacheTableRepository;
use PHPUnit\Framework\TestCase;
use Throwable;

class CacheTableRepositoryTest extends TestCase
{
	public function testGetAllKeysValidUntilReturnsArray(): void
	{
		$stmt = new \stdClass();

		$database = $this->createStub(Database::class);
		$database->method('select')->willReturnMap([
			['cache', ['k'], ['`expires` >= ?', '2025-04-16 10:12:01'], [], $stmt],
		]);
		$database->method('fetch')->willReturnOnConsecutiveCalls(
			['k' => 'value1'],
			['k' => 'value2'],
			['k' => 'value3'],
			false,
		);

		$repo = new CacheTableRepository($database);

		$this->assertSame(
			[
				'value1',
				'value2',
				'value3',
			],
			$repo->getAllKeysValidUntil('2025-04-16 10:12:01'),
		);
	}

	public function testGetAllKeysValidUntilThrowsException(): void
	{
		$database = $this->createStub(Database::class);
		$database->method('select')->willThrowException($this->createStub(Throwable::class));

		$repo = new CacheTableRepository($database);

		$this->expectException(DatabaseException::class);

		$repo->getAllKeysValidUntil('2025-04-16 10:12:01');
	}

	public function testGetAllKeysValidUntilWithPrefixReturnsArray(): void
	{
		$stmt = new \stdClass();

		$database = $this->createStub(Database::class);
		$database->method('select')->willReturnMap([
			['cache', ['k'], ['`expires` >= ?', '2025-04-16 10:12:01'], [], $stmt],
		]);
		$database->method('fetch')->willReturnOnConsecutiveCalls(
			['k' => 'value1'],
			['k' => 'value2'],
			['k' => 'value3'],
			false,
		);

		$repo = new CacheTableRepository($database);

		$this->assertSame(
			[
				'value1',
				'value2',
				'value3',
			],
			$repo->getAllKeysValidUntilWithPrefix('2025-04-16 10:12:01', 'prefix'),
		);
	}

	public function testGetAllKeysValidUntilWithPrefixThrowsException(): void
	{
		$database = $this->createStub(Database::class);
		$database->method('select')->willThrowException($this->createStub(Throwable::class));

		$repo = new CacheTableRepository($database);

		$this->expectException(DatabaseException::class);

		$repo->getAllKeysValidUntilWithPrefix('2025-04-16 10:12:01', 'prefix');
	}
}
