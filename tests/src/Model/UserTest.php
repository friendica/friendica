<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Test\MockedTestCase;
use Mockery\MockInterface;

class UserTest extends MockedTestCase
{
	private $parent;
	private $child;
	private $manage;

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

		$this->parent = [
			'uid'      => 1,
			'username' => 'maxmuster',
			'nickname' => 'Max Muster',
		];

		$this->child = [
			'uid'      => 2,
			'username' => 'johndoe',
			'nickname' => 'John Doe',
		];

		$this->manage = [
			'uid'      => 3,
			'username' => 'janesmith',
			'nickname' => 'Jane Smith',
		];
	}

	public function testIdentitiesEmpty(): void
	{
		$this->dbMock->shouldReceive('selectFirst')->with(
			'user',
			['uid', 'nickname', 'username', 'parent-uid'],
			['uid' => $this->parent['uid'], 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false],
			[],
		)->andReturn($this->parent)->once();
		$this->dbMock->shouldReceive('isResult')->with($this->parent)->andReturn(false)->once();

		$record = User::identities($this->parent['uid']);

		self::assertEquals([], $record);
	}

	public function testIdentitiesAsParent(): void
	{
		$parentSelect               = $this->parent;
		$parentSelect['parent-uid'] = null;

		// Select the user itself (=parent)
		$this->dbMock->shouldReceive('selectFirst')->with(
			'user',
			['uid', 'nickname', 'username', 'parent-uid'],
			['uid' => $this->parent['uid'], 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false],
			[],
		)->andReturn($parentSelect)->once();
		$this->dbMock->shouldReceive('isResult')->with($parentSelect)->andReturn(true)->once();

		// Select one child
		$this->dbMock->shouldReceive('select')->with(
			'user',
			['uid', 'username', 'nickname'],
			[
				'parent-uid'      => $this->parent['uid'],
				'verified'        => true,
				'blocked'         => false,
				'account_removed' => false,
				'account_expired' => false,
			],
			[],
		)->andReturn('objectReturn')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectReturn')->andReturn(true)->once();
		$this->dbMock->shouldReceive('toArray')->with('objectReturn', true, 0)->andReturn([$this->child])->once();

		// Select the manage
		$this->dbMock->shouldReceive('p')->withAnyArgs()->andReturn('objectTwo')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectTwo')->andReturn(true)->once();
		$this->dbMock->shouldReceive('toArray')->with('objectTwo', true, 0)->andReturn([$this->manage])->once();

		$record = User::identities($this->parent['uid']);

		self::assertEquals([
			$this->parent,
			$this->child,
			$this->manage,
		], $record, 'testIdentitiesAsParent: ' . print_r($record, true));
	}

	public function testIdentitiesAsChild(): void
	{
		$childSelect               = $this->child;
		$childSelect['parent-uid'] = $this->parent['uid'];

		// Select the user itself (=child)
		$this->dbMock->shouldReceive('selectFirst')->with(
			'user',
			['uid', 'nickname', 'username', 'parent-uid'],
			['uid' => $this->child['uid'], 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false],
			[],
		)->andReturn($childSelect)->once();
		$this->dbMock->shouldReceive('isResult')->with($childSelect)->andReturn(true)->once();

		// Select the parent
		$this->dbMock->shouldReceive('select')->with(
			'user',
			['uid', 'username', 'nickname'],
			[
				'uid'             => $this->parent['uid'],
				'verified'        => true,
				'blocked'         => false,
				'account_removed' => false,
				'account_expired' => false,
			],
			[],
		)->andReturn('objectReturn')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectReturn')->andReturn(true)->once();
		$this->dbMock->shouldReceive('toArray')->with('objectReturn', true, 0)->andReturn([$this->parent])->once();

		// Select the children (user & manage)
		$this->dbMock->shouldReceive('select')->with(
			'user',
			['uid', 'username', 'nickname'],
			[
				'parent-uid'      => $this->parent['uid'],
				'verified'        => true,
				'blocked'         => false,
				'account_removed' => false,
				'account_expired' => false,
			],
			[],
		)->andReturn('objectReturn')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectReturn')->andReturn(true)->once();
		$this->dbMock->shouldReceive('toArray')->with('objectReturn', true, 0)->andReturn([$this->child, $this->manage])->once();

		// Select the manage
		$this->dbMock->shouldReceive('p')->withAnyArgs()->andReturn('objectTwo')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectTwo')->andReturn(false)->once();

		$record = User::identities($this->child['uid']);

		self::assertEquals([
			$this->parent,
			$this->child,
			$this->manage,
		], $record);
	}
}
