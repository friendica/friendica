<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Exception;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Type\MemcacheCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Test\CacheLockTestCase;
use Mockery;

/**
 * @requires extension Memcache
 * @group MEMCACHE
 */
class MemcacheCacheLockTest extends CacheLockTestCase
{
	private CacheLock $lock;
	private MemcacheCache $cache;

	protected function setUp(): void
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

		try {
			$this->cache = new MemcacheCache($host, $configMock);
			$this->lock  = new CacheLock($this->cache);
		} catch (Exception $e) {
			static::markTestSkipped('Memcache is not available');
		}

		parent::setUp();
	}

	protected function getInstance(): CacheLock
	{
		return $this->lock;
	}

	protected function getCache(): ICanCacheInMemory
	{
		return $this->cache;
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
