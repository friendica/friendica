<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\Type\APCuCache;
use Friendica\Test\MemoryCacheTestCase;

/**
 * @group APCU
 */
class APCuCacheTest extends MemoryCacheTestCase
{
	protected function setUp(): void
	{
		if (!APCuCache::isAvailable()) {
			static::markTestSkipped('APCu is not available');
		}

		parent::setUp();
	}

	protected function getInstance()
	{
		$this->cache = new APCuCache('localhost');
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	/**
	 * @small
	 */
	public function testStats()
	{
		$stats = $this->instance->getStats();

		self::assertNotNull($stats['entries']);
		self::assertNotNull($stats['used_memory']);
		self::assertNotNull($stats['hits']);
		self::assertNotNull($stats['misses']);
		self::assertNotNull($stats['avail_mem']);
	}
}
