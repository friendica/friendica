<?php


namespace Friendica\Test\src\Core\Cache;


use Friendica\Core\Cache\CacheDriverFactory;

class RedisCacheDriverTest extends MemoryCacheTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		if (class_exists('Redis')) {
			try {
				$this->cache = CacheDriverFactory::create('redis');
			} catch (\Exception $exception) {
				print "Redis - TestCase failed: " . $exception->getMessage();
				throw new \Exception();
			}
			return $this->cache;
		} else {
			$this->markTestSkipped('Redis driver isn\'t available');
			return null;
		}
	}

	public function tearDown()
	{
		if (class_exists('Redis')) {
			$this->cache->clear(false);
		}
		parent::tearDown();
	}
}
