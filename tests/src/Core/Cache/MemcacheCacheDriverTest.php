<?php


namespace Friendica\Test\src\Core\Cache;


use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @requires extension memcache
 */
class MemcacheCacheDriverTest extends MemoryCacheTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		$this->cache = CacheDriverFactory::create('memcache');
		return $this->cache;

	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
