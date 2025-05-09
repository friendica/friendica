<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Type\APCuCache;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Test\CacheLockTestCase;

/**
 * @group APCU
 */
class APCuCacheLockTest extends CacheLockTestCase
{
	private APCuCache $cache;
	private ICanLock $lock;

	protected function setUp(): void
	{
		if (!APCuCache::isAvailable()) {
			static::markTestSkipped('APCu is not available');
		}

		$this->cache = new APCuCache('localhost');
		$this->lock  = new CacheLock($this->cache);

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
}
