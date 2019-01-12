<?php


namespace Friendica\Test\src\Core\Cache;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Test\Util\Mocks\ConfigMockTrait;

/**
 * @requires extension memcached
 */
class MemcachedCacheDriverTest extends MemoryCacheTest
{
	use ConfigMockTrait;

	protected function getInstance()
	{
		$this->mockConfigGet('system', 'memcached_hosts', [0 => "localhost, 11211"]);

		/// @todo not needed anymore with new Logging 2019.03
		$this->mockConfigGet('system', 'debugging', false);
		$this->mockConfigGet('system', 'logfile', 'friendica.log');
		$this->mockConfigGet('system', 'loglevel', '0');

		$this->cache = CacheDriverFactory::create('memcached');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
