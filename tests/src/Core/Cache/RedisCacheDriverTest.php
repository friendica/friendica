<?php


namespace Friendica\Test\src\Core\Cache;


use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @requires extension redis
 */
class RedisCacheDriverTest extends MemoryCacheTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		$this->cache = CacheDriverFactory::create('redis');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
