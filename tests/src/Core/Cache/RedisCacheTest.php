<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Cache;

use Exception;
use Friendica\Core\Cache\Type\RedisCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MemoryCacheTestCase;
use Mockery;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('redis')]
#[\PHPUnit\Framework\Attributes\Group('REDIS')]
class RedisCacheTest extends MemoryCacheTestCase
{
	protected function getInstance()
	{
		$configMock = Mockery::mock(IManageConfigValues::class);

		$host = $_SERVER['REDIS_HOST'] ?? 'localhost';
		$port = $_SERVER['REDIS_PORT'] ?? 6379;

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_host')
			->andReturn($host);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_port')
			->andReturn($port);

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_db', 0)
			->andReturn(0);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_password')
			->andReturn(null);

		try {
			$this->cache = new RedisCache($host, $configMock);
		} catch (Exception $e) {
			static::markTestSkipped('Redis is not available. Failure: ' . $e->getMessage());
		}
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	public function testAddSetsTTL(): void
	{
		$this->instance->add('ttl_probe', 'value', 60);

		$ttl = $this->redisClient()->ttl($this->redisKey('ttl_probe'));

		self::assertGreaterThan(0, $ttl);
		self::assertLessThanOrEqual(60, $ttl);
	}

	public function testAddDefaultTTL(): void
	{
		$this->instance->add('ttl_probe', 'value');

		self::assertGreaterThan(0, $this->redisClient()->ttl($this->redisKey('ttl_probe')));
	}

	public function testAddWithoutTTLNeverExpires(): void
	{
		$this->instance->add('ttl_probe', 'value', 0);

		self::assertSame(-1, $this->redisClient()->ttl($this->redisKey('ttl_probe')));
	}

	private function redisClient(): \Redis
	{
		$redis = new \Redis();
		$redis->connect($_SERVER['REDIS_HOST'] ?? 'localhost', (int) ($_SERVER['REDIS_PORT'] ?? 6379));

		return $redis;
	}

	private function redisKey(string $suffix): string
	{
		$keys = $this->redisClient()->keys('*' . $suffix);
		self::assertCount(1, $keys);

		return $keys[0];
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
