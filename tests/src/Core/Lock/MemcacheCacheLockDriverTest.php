<?php


namespace Friendica\Test\src\Core\Lock;


use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

class MemcacheCacheLockDriverTest extends LockTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		if (class_exists('Memcache')) {
			try {
				$this->cache = CacheDriverFactory::create('memcache');
			} catch (\Exception $exception) {
				print "Memcache - TestCase failed: " . $exception->getMessage();
				throw new \Exception();
			}
			return new CacheLockDriver($this->cache);
		} else {
			$this->markTestSkipped('Memcache driver isn\'t available');
			return null;
		}
	}

	public function tearDown()
	{
		if (class_exists('Memcache')) {
			$this->cache->clear();
		}
		parent::tearDown();
	}
}
