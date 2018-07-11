<?php

namespace Friendica\Test\src\Core\Lock;


use Friendica\Core\Lock\SemaphoreLockDriver;

class SemaphoreLockDriverTest extends LockTest
{
	/**
	 * @var \Friendica\Core\Lock\SemaphoreLockDriver
	 */
	private $semaphoreLockDriver;

	protected function getInstance()
	{
		$this->semaphoreLockDriver = new SemaphoreLockDriver();
		return $this->semaphoreLockDriver;
	}

	public function tearDown()
	{
		$this->semaphoreLockDriver->releaseAll();
		parent::tearDown();
	}

	function testLockTTL()
	{
		// Semaphore doesn't work with TTL
		return true;
	}
}
