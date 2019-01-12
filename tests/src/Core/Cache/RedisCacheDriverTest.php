<?php


namespace Friendica\Test\src\Core\Cache;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Test\Util\Mocks\ConfigMockTrait;

/**
 * @requires extension redis
 */
class RedisCacheDriverTest extends MemoryCacheTest
{
	use ConfigMockTrait;

	protected function getInstance()
	{
		$this->mockConfigGet('system', 'redis_host', 'localhost');
		$this->mockConfigGet('system', 'redis_port', null);

		$this->cache = CacheDriverFactory::create('redis');
		return $this->cache;
	}

	public function tearDown()
	{
		parent::tearDown();
	}
}
