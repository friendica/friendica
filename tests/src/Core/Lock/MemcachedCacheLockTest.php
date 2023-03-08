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

namespace Friendica\Test\src\Core\Lock;

use Exception;
use Friendica\Core\Cache\Type\MemcachedCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Type\CacheLock;
use Mockery;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 * @group MEMCACHED
 */
class MemcachedCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		$configMock = Mockery::mock(IManageConfigValues::class);

		$host = $_SERVER['MEMCACHED_HOST'] ?? 'localhost';
		$port = $_SERVER['MEMCACHED_PORT'] ?? '11211';

		$configMock
			->shouldReceive('get')
			->with('system', 'memcached_hosts')
			->andReturn([0 => $host . ', ' . $port]);

		$logger = new NullLogger();

		$lock = null;

		try {
			$cache = new MemcachedCache($host, $configMock, $logger);
			$lock = new CacheLock($cache);
		} catch (Exception $e) {
			static::markTestSkipped('Memcached is not available');
		}

		return $lock;
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGetLocks()
	{
		static::markTestIncomplete('Race condition because of too fast getLocks() which uses a workaround');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGetLocksWithPrefix()
	{
		static::markTestIncomplete('Race condition because of too fast getLocks() which uses a workaround');
	}
}
