<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Exception;
use Friendica\Core\Cache\Type\MemcachedCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Test\LockTestCase;
use Mockery;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 * @group MEMCACHED
 */
class MemcachedCacheLockTest extends LockTestCase
{
	protected function getInstance()
	{
		$configMock = Mockery::mock(IManageConfigValues::class);

		$host = $_SERVER['MEMCACHED_HOST'] ?? 'localhost';
		$port = $_SERVER['MEMCACHED_PORT'] ?? '11211';

		$configMock
			->shouldReceive('get')
			->with('system', 'memcached_hosts')
			->andReturn([0 => $host . ', ' . $port]);

		$logger = new NullLogger();

		$lock = null;

		try {
			$cache = new MemcachedCache($host, $configMock, $logger);
			$lock  = new CacheLock($cache);
		} catch (Exception $e) {
			static::markTestSkipped('Memcached is not available');
		}

		return $lock;
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGetLocks()
	{
		static::markTestIncomplete('Race condition because of too fast getLocks() which uses a workaround');
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGetLocksWithPrefix()
	{
		static::markTestIncomplete('Race condition because of too fast getLocks() which uses a workaround');
	}
}
