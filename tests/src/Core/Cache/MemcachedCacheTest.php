<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Cache;

use Exception;
use Friendica\Core\Cache\Type\MemcachedCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MemoryCacheTestCase;
use Mockery;
use Psr\Log\NullLogger;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('memcached')]
#[\PHPUnit\Framework\Attributes\Group('MEMCACHED')]
class MemcachedCacheTest extends MemoryCacheTestCase
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

		try {
			$this->cache = new MemcachedCache($host, $configMock, $logger);
		} catch (Exception) {
			static::markTestSkipped('Memcached is not available');
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
