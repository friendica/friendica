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

namespace Friendica\Test\src\Core\Worker\Repository;

use Friendica\Core\Worker\Factory;
use Friendica\Core\Worker\Repository;
use Friendica\DI;
use Friendica\Test\FixtureTest;
use Psr\Log\NullLogger;

class ProcessTest extends FixtureTest
{
	public function testStandardProcess()
	{
		$factory    = new Factory\Process(new NullLogger());
		$repository = new Repository\Process(DI::dba(), new NullLogger(), $factory, []);

		$entityNew = $repository->create(getmypid(), 'test');

		self::assertEquals(getmypid(), $entityNew->pid);
		self::assertEquals('test', $entityNew->command);
		self::assertLessThanOrEqual(new \DateTime('now', new \DateTimeZone('UTC')), $entityNew->created);
		self::assertEquals(php_uname('n'), $entityNew->hostname);
	}

	public function testHostnameEnv()
	{
		$factory    = new Factory\Process(new NullLogger());
		$repository = new Repository\Process(DI::dba(), new NullLogger(), $factory, [Repository\Process::NODE_ENV => 'test_node']);

		$entityNew = $repository->create(getmypid(), 'test');

		self::assertEquals(getmypid(), $entityNew->pid);
		self::assertEquals('test', $entityNew->command);
		self::assertLessThanOrEqual(new \DateTime('now', new \DateTimeZone('UTC')), $entityNew->created);
		self::assertEquals('test_node', $entityNew->hostname);
	}
}
