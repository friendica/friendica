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

namespace Friendica\Test\src\Core\Cache;

use Exception;
use Friendica\Core\Cache\Type\RedisCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Mockery;

/**
 * @requires extension redis
 * @group REDIS
 */
class RedisCacheTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$configMock = Mockery::mock(IManageConfigValues::class);

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

		try {
			$this->cache = new \Friendica\Core\Cache\Type\RedisCache($host, $configMock);
		} catch (Exception $e) {
			static::markTestSkipped('Redis is not available. Failure: ' . $e->getMessage());
		}
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
