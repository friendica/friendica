<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\MemcachedCache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Lock\CacheLock;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 * @group MEMCACHED
 */
class MemcachedCacheLockTest extends LockTest
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

		$lock = null;

		try {
			$cache = new MemcachedCache($host, $configMock, $logger);
			$lock = new CacheLock($cache);
		} catch (\Exception $e) {
			$this->markTestSkipped('Memcached is not available');
		}

		return $lock;
	}

	public function testGetLocks()
	{
		$this->markTestIncomplete('Race condition because of too fast getLocks() which uses a workaround');
	}

	public function testGetLocksWithPrefix()
	{
		$this->markTestIncomplete('Race condition because of too fast getLocks() which uses a workaround');
	}
}
