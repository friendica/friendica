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
		try {
			$this->cache = CacheDriverFactory::create('memcache');
		} catch (\Exception $exception) {
			throw new \Exception("Memcache - TestCase failed: " . $exception->getMessage(), $exception->getCode(), $exception);
		}
		return $this->cache;

	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
