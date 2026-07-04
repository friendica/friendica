<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Cache;

use Exception;
use Friendica\Core\Cache\Type\MemcacheCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MemoryCacheTestCase;
use Mockery;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('memcache')]
#[\PHPUnit\Framework\Attributes\Group('MEMCACHE')]
class MemcacheCacheTest extends MemoryCacheTestCase
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

		try {
			$this->cache = new MemcacheCache($host, $configMock);
		} catch (Exception) {
			static::markTestSkipped('Memcache is not available');
		}
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataSimple')]
	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testGetAllKeys($value1, $value2, $value3, $value4): void
	{
		static::markTestIncomplete('Race condition because of too fast getAllKeys() which uses a workaround');
	}

	public function testStats(): void
	{
		$stats = $this->instance->getStats();

		self::assertNotNull($stats['version']);
		self::assertIsNumeric($stats['hits']);
		self::assertIsNumeric($stats['misses']);
		self::assertIsNumeric($stats['evictions']);
		self::assertIsNumeric($stats['entries']);
		self::assertIsNumeric($stats['used_memory']);
		self::assertGreaterThan(0, $stats['connected_clients']);
		self::assertGreaterThan(0, $stats['uptime']);
	}
}
