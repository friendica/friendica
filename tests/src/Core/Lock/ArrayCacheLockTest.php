<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Test\CacheLockTestCase;

class ArrayCacheLockTest extends CacheLockTestCase
{
	private CacheLock $lock;
	private ArrayCache $cache;

	protected function setUp(): void
	{
		$this->cache = new ArrayCache('localhost');
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

	/**
	 * @doesNotPerformAssertions
	 */
	public function testLockTTL()
	{
		self::markTestSkipped("ArrayCache doesn't support TTL");
	}
}
