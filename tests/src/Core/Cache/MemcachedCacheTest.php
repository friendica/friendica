<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\MemcachedCache;
use Friendica\Core\Config\IConfiguration;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 * @group MEMCACHED
 */
class MemcachedCacheTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(IConfiguration::class);

		$host = $_SERVER['MEMCACHED_HOST'] ?? 'localhost';

		$configMock
			->shouldReceive('get')
			->with('system', 'memcached_hosts')
			->andReturn([0 => $host . ', 11211']);

		$logger = new NullLogger();

		try {
			$this->cache = new MemcachedCache($host, $configMock, $logger);
		} catch (\Exception $exception) {
			$this->markTestSkipped('Memcached is not available');
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
