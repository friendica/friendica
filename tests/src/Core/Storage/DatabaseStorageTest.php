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

namespace Friendica\Test\src\Core\Storage;

use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Storage\Type\Database;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Profiler;
use Psr\Log\NullLogger;

class DatabaseStorageTest extends StorageTest
{
	use DatabaseTestTrait;
	use VFSTrait;

	protected function setUp(): void
	{
		$this->setUpVfsDir();

		$this->setUpDb();

		parent::setUp();
	}

	protected function getInstance()
	{
		$logger   = new NullLogger();
		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('startRecording');
		$profiler->shouldReceive('stopRecording');
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new Config();
		$loader        = (new Config())->createConfigFileLoader($this->root->url(), []);
		$configCache   = $configFactory->createCache($loader);

		$dba = new StaticDatabase($configCache, $profiler, $logger);

		return new Database($dba);
	}

	protected function tearDown(): void
	{
		$this->tearDownDb();

		parent::tearDown();
	}
}
