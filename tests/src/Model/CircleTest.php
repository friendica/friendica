<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Circle;
use Friendica\Test\MockedTestCase;
use Mockery\MockInterface;

class CircleTest extends MockedTestCase
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

	public function testExistsWithoutUid(): void
	{
		$this->dbMock->shouldReceive('exists')->with(
			'group',
			['id' => 17, 'deleted' => false],
		)->andReturn(true)->once();

		self::assertTrue(Circle::exists(17));
	}

	public function testExistsKeepsTheCircleIdWhenAUidIsGiven(): void
	{
		$this->dbMock->shouldReceive('exists')->with(
			'group',
			['id' => 17, 'deleted' => false, 'uid' => 42],
		)->andReturn(true)->once();

		self::assertTrue(Circle::exists(17, 42));
	}

	public function testExistsForAnotherUid(): void
	{
		$this->dbMock->shouldReceive('exists')->with(
			'group',
			['id' => 17, 'deleted' => false, 'uid' => 41],
		)->andReturn(false)->once();

		self::assertFalse(Circle::exists(17, 41));
	}
}
