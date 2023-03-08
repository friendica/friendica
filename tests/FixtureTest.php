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
 * FixtureTest class.
 */

namespace Friendica\Test;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\Router;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Type\Memory;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;

/**
 * Parent class for test cases requiring fixtures
 */
abstract class FixtureTest extends MockedTest
{
	use FixtureTestTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpFixtures();
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}
}
