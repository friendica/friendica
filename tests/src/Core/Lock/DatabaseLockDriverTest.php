<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\Type\DatabaseLock;
use Friendica\Core\Config\Factory\Config;
use Friendica\DI;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Profiler;
use Mockery;
use Psr\Log\NullLogger;

class DatabaseLockDriverTest extends LockTest
{
	use VFSTrait;
	use DatabaseTestTrait;

	protected $pid = 123;

	protected function setUp(): void
	{
		$this->setUpVfsDir();

		$this->setUpDb();

		parent::setUp();
	}

	protected function getInstance()
	{
		return new DatabaseLock(DI::dba(), $this->pid);
	}

	protected function tearDown(): void
	{
		$this->tearDownDb();

		parent::tearDown();
	}
}
