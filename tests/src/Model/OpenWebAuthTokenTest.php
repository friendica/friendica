<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\OpenWebAuthToken;
use Friendica\Test\MockedTestCase;
use Friendica\Util\DateTimeFormat;
use Mockery\MockInterface;

class OpenWebAuthTokenTest extends MockedTestCase
{
	/** @var Database|MockInterface */
	private $dbMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->dbMock = \Mockery::mock(Database::class);

		$diceMock = \Mockery::mock(Dice::class)->makePartial();
		/** @var Dice|MockInterface $diceMock */
		$diceMock = $diceMock->addRules(include __DIR__ . '/../../../static/dependencies.config.php');
		$diceMock->shouldReceive('create')->withArgs([Database::class])->andReturn($this->dbMock);
		DI::init($diceMock, true);
	}

	public function testPurgeComparesAgainstTheCutoff(): void
	{
		$cutoff = null;

		$this->dbMock->shouldReceive('delete')->withArgs(function (string $table, array $condition) use (&$cutoff) {
			$cutoff = $condition[2];
			return $table === 'openwebauth-token' && $condition[1] === 'owt';
		})->andReturn(true)->once();

		OpenWebAuthToken::purge('owt', '3 MINUTE');

		self::assertLessThan(DateTimeFormat::utcNow(), $cutoff);
		self::assertGreaterThan(DateTimeFormat::utc('now - 4 MINUTE'), $cutoff);
	}
}
