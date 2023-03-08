<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Model;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Test\MockedTest;
use Mockery\MockInterface;

class UserTest extends MockedTest
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
			'nickname' => 'Max Muster'
		];

		$this->child = [
			'uid'      => 2,
			'username' => 'johndoe',
			'nickname' => 'John Doe'
		];

		$this->manage = [
			'uid'      => 3,
			'username' => 'janesmith',
			'nickname' => 'Jane Smith'
		];
	}

	public function testIdentitiesEmpty()
	{
		$this->dbMock->shouldReceive('selectFirst')->with('user',
			['uid', 'nickname', 'username', 'parent-uid'],['uid' => $this->parent['uid']], [])->andReturn($this->parent)->once();
		$this->dbMock->shouldReceive('isResult')->with($this->parent)->andReturn(false)->once();

		$record = User::identities($this->parent['uid']);

		self::assertEquals([], $record);
	}

	public function testIdentitiesAsParent()
	{
		$parentSelect               = $this->parent;
		$parentSelect['parent-uid'] = 0;

		// Select the user itself (=parent)
		$this->dbMock->shouldReceive('selectFirst')->with('user',
			['uid', 'nickname', 'username', 'parent-uid'],['uid' => $this->parent['uid']], [])->andReturn($parentSelect)->once();
		$this->dbMock->shouldReceive('isResult')->with($parentSelect)->andReturn(true)->once();

		// Select one child
		$this->dbMock->shouldReceive('select')->with('user',
			['uid', 'username', 'nickname'],
			[
				'parent-uid'      => $this->parent['uid'],
				'account_removed' => false
			], [])->andReturn('objectReturn')->once();
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
			$this->manage
		], $record);
	}

	public function testIdentitiesAsChild()
	{
		$childSelect               = $this->child;
		$childSelect['parent-uid'] = $this->parent['uid'];

		// Select the user itself (=child)
		$this->dbMock->shouldReceive('selectFirst')->with('user',
			['uid', 'nickname', 'username', 'parent-uid'],['uid' => $this->child['uid']], [])->andReturn($childSelect)->once();
		$this->dbMock->shouldReceive('isResult')->with($childSelect)->andReturn(true)->once();

		// Select the parent
		$this->dbMock->shouldReceive('select')->with('user',
			['uid', 'username', 'nickname'],
			[
				'uid'      => $this->parent['uid'],
				'account_removed' => false
			], [])->andReturn('objectReturn')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectReturn')->andReturn(true)->once();
		$this->dbMock->shouldReceive('toArray')->with('objectReturn', true, 0)->andReturn([$this->parent])->once();

		// Select the childs (user & manage)
		$this->dbMock->shouldReceive('select')->with('user',
			['uid', 'username', 'nickname'],
			[
				'parent-uid'      => $this->parent['uid'],
				'account_removed' => false
			], [])->andReturn('objectReturn')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectReturn')->andReturn(true)->once();
		$this->dbMock->shouldReceive('toArray')->with('objectReturn', true, 0)->andReturn([$this->child, $this->manage])->once();

		// Select the manage
		$this->dbMock->shouldReceive('p')->withAnyArgs()->andReturn('objectTwo')->once();
		$this->dbMock->shouldReceive('isResult')->with('objectTwo')->andReturn(false)->once();

		$record = User::identities($this->child['uid']);

		self::assertEquals([
			$this->parent,
			$this->child,
			$this->manage
		], $record);
	}
}
