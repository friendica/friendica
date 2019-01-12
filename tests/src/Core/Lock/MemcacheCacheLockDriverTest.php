<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;
use Friendica\Test\Util\Mocks\ConfigMockTrait;

/**
 * @requires extension Memcache
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MemcacheCacheLockDriverTest extends LockTest
{
	use ConfigMockTrait;

	protected function getInstance()
	{
		$this->mockConfigGet('system', 'memcache_host', '127.0.0.1');
		$this->mockConfigGet('system', 'memcache_port', '11211');

		return new CacheLockDriver(CacheDriverFactory::create('memcache'));
	}
}
