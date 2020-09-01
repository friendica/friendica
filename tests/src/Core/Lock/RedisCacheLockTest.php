<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\Core\Cache\RedisCache;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Lock\CacheLock;
use Mockery;

/**
 * @requires extension redis
 * @group REDIS
 */
class RedisCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		$configMock = Mockery::mock(IConfig::class);

		$host = $_SERVER['REDIS_HOST'] ?? 'localhost';
		$port = $_SERVER['REDIS_PORT'] ?? 6379;

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_host')
			->andReturn($host);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_port')
			->andReturn($port);

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_db', 0)
			->andReturn(0);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_password')
			->andReturn(null);

		$lock = null;

		try {
			$cache = new RedisCache($host, $configMock);
			$lock = new CacheLock($cache, $this->hostname);
		} catch (Exception $e) {
			static::markTestSkipped('Redis is not available. Error: ' . $e->getMessage());
		}

		return $lock;
	}
}
