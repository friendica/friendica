<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\MemcacheCache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Lock\CacheLock;

/**
 * @requires extension Memcache
 * @group MEMCACHE
 */
class MemcacheCacheLockTest extends LockTest
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

		$lock = null;

		try {
			$cache = new MemcacheCache($host, $configMock);
			$lock = new CacheLock($cache);
		} catch (\Exception $e) {
			$this->markTestSkipped('Memcache is not available');
		}

		return $lock;
	}

	/**
	 * @small
	 */
	public function testGetLocks()
	{
		$this->markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}

	/**
	 * @small
	 */
	public function testGetLocksWithPrefix()
	{
		$this->markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}
}
