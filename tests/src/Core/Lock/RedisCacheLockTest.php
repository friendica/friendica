<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Exception;
use Friendica\Core\Cache\Type\RedisCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Test\LockTestCase;
use Mockery;

/**
 * @requires extension redis
 * @group REDIS
 */
class RedisCacheLockTest extends LockTestCase
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

		$lock = null;

		try {
			$cache = new RedisCache($host, $configMock);
			$lock  = new CacheLock($cache);
		} catch (Exception $e) {
			static::markTestSkipped('Redis is not available. Error: ' . $e->getMessage());
		}

		return $lock;
	}
}
