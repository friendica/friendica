<?php


namespace Friendica\Test\src\Core\Cache;


use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @requires extension memcached
 */
class MemcachedCacheDriverTest extends MemoryCacheTest
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
			throw new \Exception("Memcached - TestCase failed: " . $exception->getMessage(), $exception->getCode(), $exception);
		}
		return $this->cache;

	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
