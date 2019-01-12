<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Test\Util\Mocks\DBAMockTrait;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DatabaseCacheDriverTest extends CacheTest
{
	use DBAMockTrait;

	protected function getInstance()
	{
		$this->cache = CacheDriverFactory::create('database');

		// mocking first clear
		$this->mockDelete('cache', [0 => '`k` IS NOT NULL ']);

		return $this->cache;
	}

	public function tearDown()
	{
		parent::tearDown();
	}
}
