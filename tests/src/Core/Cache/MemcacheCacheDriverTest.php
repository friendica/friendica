<?php


namespace Friendica\Test\src\Core\Cache;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Test\Util\Mocks\ConfigMockTrait;

/**
 * @requires extension memcache
 */
class MemcacheCacheDriverTest extends MemoryCacheTest
{
	use ConfigMockTrait;

	protected function getInstance()
	{
		$this->mockConfigGet('system', 'memcache_host', '127.0.0.1');
		$this->mockConfigGet('system', 'memcache_port', '11211');

		$this->cache = CacheDriverFactory::create('memcache');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
