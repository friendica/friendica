<?php


namespace Friendica\Test\src\Core\Lock;


use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension Memcache
 */
class MemcacheCacheLockDriverTest extends LockTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		try {
			$this->cache = CacheDriverFactory::create('memcache');
		} catch (\Exception $exception) {
			print "Memcache - TestCase failed: " . $exception->getMessage();
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
