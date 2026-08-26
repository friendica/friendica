<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Database\Repository;

use Friendica\Database\Database;
use Friendica\Database\DatabaseException;
use Friendica\Database\Repository\UserdTableRepository;
use Friendica\Repository\DeletedUserRepository;
use PHPUnit\Framework\TestCase;

class UserdTableRepositoryTest extends TestCase
{
	public function testImplementationOfInterfaces(): void
	{
		$repo = new UserdTableRepository($this->createMock(Database::class));

		$this->assertInstanceOf(DeletedUserRepository::class, $repo);
	}

	public function testInsertByUsernameCallsDatabase(): void
	{
		$database = $this->createMock(Database::class);
		$database->expects($this->once())->method('insert')->willReturnMap([
			['userd', ['username' => 'test'], 0, true],
		]);

		$repo = new UserdTableRepository($database);

		$repo->insertByUsername('test');
	}

	public function testInsertByUsernameThrowsException(): void
	{
		$database = $this->createMock(Database::class);
		$database->expects($this->exactly(2))->method('throwExceptionsOnErrors');
		$database->expects($this->once())->method('insert')->willThrowException(
			new DatabaseException('An error occured.', 0, 'SQL query'),
		);

		$repo = new UserdTableRepository($database);

		$this->expectException(DatabaseException::class);

		$repo->insertByUsername('test');
	}

	public function testExistsByUsernameReturnsTrue(): void
	{
		$database = $this->createStub(Database::class);
		$database->method('exists')->willReturnMap([
			['userd', ['username' => 'test'], true],
		]);

		$repo = new UserdTableRepository($database);

		$this->assertTrue($repo->existsByUsername('test'));
	}

	public function testExistsByUsernameReturnsFalse(): void
	{
		$database = $this->createStub(Database::class);
		$database->method('exists')->willReturnMap([
			['userd', ['username' => 'test'], false],
		]);

		$repo = new UserdTableRepository($database);

		$this->assertFalse($repo->existsByUsername('test'));
	}
}
