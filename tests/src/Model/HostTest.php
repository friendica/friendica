<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\Database\Database;
use Friendica\Model\Host;
use Friendica\Test\MockedTest;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

class HostTest extends MockedTest {

	/** @var Database|MockInterface */
	private $dba;

	protected function setUp()
	{
		parent::setUp();

		$this->dba = \Mockery::mock(Database::class);
	}

	public function testExistingHost() {
		$this->dba->shouldReceive('selectFirst')->andReturn(['id' => 1])->once();

		$host = new Host($this->dba, new NullLogger(), []);

		$this->assertEquals(php_uname('n'), $host->getName());
		$this->assertEquals(1, $host->getId());

		// Test again to check that the db calls are just used once
		$this->assertEquals(php_uname('n'), $host->getName());
		$this->assertEquals(1, $host->getId());
	}

	public function testHostOverride() {
		$this->dba->shouldReceive('selectFirst')->andReturn(['id' => 1])->once();

		$host = new Host($this->dba, new NullLogger(), [Host::ENV_VARIABLE => 'testserver.test']);

		$this->assertEquals('testserver.test', $host->getName());
		$this->assertEquals(1, $host->getId());

		// Test again to check that the db calls are just used once
		$this->assertEquals('testserver.test', $host->getName());
		$this->assertEquals(1, $host->getId());
	}

	public function testEmptyHostname() {
		$host = new Host($this->dba, new NullLogger(), [Host::ENV_VARIABLE => ' ']);

		$this->assertEmpty($host->getName());
		$this->assertEquals(-1, $host->getId());
	}

	public function testNewHostname() {
		$this->dba->shouldReceive('selectFirst')->andReturn([])->once();
		$this->dba->shouldReceive('replace')->once();
		$this->dba->shouldReceive('selectFirst')->andReturn(['id' =>  1])->once();

		$host = new Host($this->dba, new NullLogger(), []);

		$this->assertEquals(php_uname('n'), $host->getName());
		$this->assertEquals(1, $host->getId());

		// Test again to check that the db calls are just used once
		$this->assertEquals(php_uname('n'), $host->getName());
		$this->assertEquals(1, $host->getId());
	}

	public function testBadInsert() {
		$this->dba->shouldReceive('selectFirst')->andReturn([])->once();
		$this->dba->shouldReceive('replace')->once();
		$this->dba->shouldReceive('selectFirst')->andReturn([])->once();

		$host = new Host($this->dba, new NullLogger(), []);

		$this->assertEquals(php_uname('n'), $host->getName());
		$this->assertEquals(-1, $host->getId());
	}
}
