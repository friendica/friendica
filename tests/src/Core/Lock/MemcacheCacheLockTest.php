<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Exception;
use Friendica\Core\Cache\Type\MemcacheCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Test\LockTestCase;
use Mockery;

/**
 * @requires extension Memcache
 * @group MEMCACHE
 */
class MemcacheCacheLockTest extends LockTestCase
{
	protected function getInstance()
	{
		$configMock = Mockery::mock(IManageConfigValues::class);

		$host = $_SERVER['MEMCACHE_HOST'] ?? 'localhost';
		$port = $_SERVER['MEMCACHE_PORT'] ?? '11211';

		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_host')
			->andReturn($host);
		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_port')
			->andReturn($port);

		$lock = null;

		try {
			$cache = new MemcacheCache($host, $configMock);
			$lock  = new CacheLock($cache);
		} catch (Exception $e) {
			static::markTestSkipped('Memcache is not available');
		}

		return $lock;
	}

	/**
	 * @small
	 * @doesNotPerformAssertions
	 */
	public function testGetLocks()
	{
		static::markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}

	/**
	 * @small
	 * @doesNotPerformAssertions
	 */
	public function testGetLocksWithPrefix()
	{
		static::markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}
}
