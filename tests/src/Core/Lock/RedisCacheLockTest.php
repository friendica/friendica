<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\RedisCache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Lock\CacheLock;

/**
 * @requires extension redis
 * @group REDIS
 */
class RedisCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(IConfiguration::class);

		$host = $_SERVER['REDIS_HOST'] ?? 'localhost';

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_host')
			->andReturn($host);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_port')
			->andReturn(null);

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_db', 0)
			->andReturn(3);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_password')
			->andReturn(null);

		$lock = null;

		try {
			$cache = new RedisCache($host, $configMock);
			$lock = new CacheLock($cache);
		} catch (\Exception $e) {
			$this->markTestSkipped('Redis is not available');
		}

		return $lock;
	}
}
