<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\MemcacheCache;
use Friendica\Core\Config\IConfiguration;

/**
 * @requires extension memcache
 * @group MEMCACHE
 */
class MemcacheCacheTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(IConfiguration::class);

		$host = $_SERVER['MEMCACHE_HOST'] ?? 'localhost';

		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_host')
			->andReturn($host);
		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_port')
			->andReturn(11211);

		try {
			$this->cache = new MemcacheCache($host, $configMock);
		} catch (\Exception $e) {
			$this->markTestSkipped('Memcache is not available');
		}
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	/**
	 * @small
	 *
	 * @dataProvider dataSimple
	 */
	public function testGetAllKeys($value1, $value2, $value3)
	{
		$this->markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}
}
