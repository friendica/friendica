<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\SemaphoreLockDriver;
use Friendica\Test\Util\Mocks\ConfigMockTrait;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SemaphoreLockDriverTest extends LockTest
{
	use ConfigMockTrait;

	protected function getInstance()
	{
		$this->mockConfigGet('system', 'temppath', '/tmp/' . $this->app->getHostName());
		$this->mockConfigSet('system', 'temppath', '/tmp/' . $this->app->getHostName());

		/// @todo not needed anymore with new Logging 2019.03
		$this->mockConfigGet('system', 'debugging', false);
		$this->mockConfigGet('system', 'logfile', 'friendica.log');
		$this->mockConfigGet('system', 'loglevel', '0');

		return new SemaphoreLockDriver();
	}

	function testLockTTL()
	{
		// Semaphore doesn't work with TTL
		return true;
	}
}
