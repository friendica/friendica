<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension Memcache
 */
class MemcacheCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		return new CacheLockDriver(CacheDriverFactory::create('memcache'));
	}
}
