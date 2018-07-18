<?php


namespace Friendica\Test\src\Core\Lock;


use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension memcached
 */
class MemcachedCacheLockDriverTest extends LockTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		$this->cache = CacheDriverFactory::create('memcached');
		return new CacheLockDriver($this->cache);
	}

	public function tearDown()
	{
		$this->cache->clear();
		parent::tearDown();
	}
}
