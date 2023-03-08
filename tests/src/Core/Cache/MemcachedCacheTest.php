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
use Friendica\Core\Cache\Type\MemcachedCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Mockery;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 * @group MEMCACHED
 */
class MemcachedCacheTest extends MemoryCacheTest
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

		try {
			$this->cache = new MemcachedCache($host, $configMock, $logger);
		} catch (Exception $exception) {
			static::markTestSkipped('Memcached is not available');
		}
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	/**
	 * @small
	 *
	 * @dataProvider dataSimple
	 * @doesNotPerformAssertions
	 */
	public function testGetAllKeys($value1, $value2, $value3)
	{
		static::markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}
}
