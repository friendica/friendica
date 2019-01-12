<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;
use Friendica\Test\Util\Mocks\ConfigMockTrait;

/**
 * @requires extension redis
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RedisCacheLockDriverTest extends LockTest
{
	use ConfigMockTrait;

	protected function getInstance()
	{
		$this->mockConfigGet('system', 'redis_host', 'localhost');
		$this->mockConfigGet('system', 'redis_port', null);

		return new CacheLockDriver(CacheDriverFactory::create('redis'));

	}
}
