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
		try {
			$this->cache = CacheDriverFactory::create('memcached');
		} catch (\Exception $exception) {
			print "Memcached - TestCase failed: " . $exception->getMessage();
			throw new \Exception();
		}
		return new CacheLockDriver($this->cache);
	}

	public function tearDown()
	{
		$this->cache->clear();
		parent::tearDown();
	}
}
