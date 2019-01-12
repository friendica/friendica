<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;
use Friendica\Test\Util\Mocks\ConfigMockTrait;

/**
 * @requires extension memcached
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MemcachedCacheLockDriverTest extends LockTest
{
	use ConfigMockTrait;

	protected function getInstance()
	{
		$this->mockConfigGet('system', 'memcached_hosts', [0 => "localhost, 11211"]);

		/// @todo not needed anymore with new Logging 2019.03
		$this->mockConfigGet('system', 'debugging', false);
		$this->mockConfigGet('system', 'logfile', 'friendica.log');
		$this->mockConfigGet('system', 'loglevel', '0');

		return new CacheLockDriver(CacheDriverFactory::create('memcached'));
	}
}
