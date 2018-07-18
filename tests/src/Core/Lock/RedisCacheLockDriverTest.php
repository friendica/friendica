<?php


namespace Friendica\Test\src\Core\Lock;


use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension redis
 */
class RedisCacheLockDriverTest extends LockTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		try {
			$this->cache = CacheDriverFactory::create('redis');
		} catch (\Exception $exception) {
			print "Redis - TestCase failed: " . $exception->getMessage();
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
