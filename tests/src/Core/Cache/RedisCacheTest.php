<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\RedisCache;
use Friendica\Core\Config\IConfiguration;

/**
 * @requires extension redis
 * @group REDIS
 */
class RedisCacheTest extends MemoryCacheTest
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

		try {
			$this->cache = new RedisCache($host, $configMock);
		} catch (\Exception $e) {
			$this->markTestSkipped('Redis is not available.');
		}
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
