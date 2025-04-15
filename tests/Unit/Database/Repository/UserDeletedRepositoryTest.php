<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Database\Repository;

use Friendica\Database\Database;
use Friendica\Database\Repository\UserDeletedRepository;
use PHPUnit\Framework\TestCase;

class UserDeletedRepositoryTest extends TestCase
{
	public function testExistsByUsernameReturnsTrue(): void
	{
		$database = $this->createStub(Database::class);
		$database->method('exists')->willReturnMap([
			['userd', ['username' => 'test'], true],
		]);

		$repo = new UserDeletedRepository($database);

		$this->assertTrue($repo->existsByUsername('test'));
	}

	public function testExistsByUsernameReturnsFalse(): void
	{
		$database = $this->createStub(Database::class);
		$database->method('exists')->willReturnMap([
			['userd', ['username' => 'test'], false],
		]);

		$repo = new UserDeletedRepository($database);

		$this->assertFalse($repo->existsByUsername('test'));
	}
}
