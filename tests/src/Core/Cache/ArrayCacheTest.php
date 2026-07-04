<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Test\MemoryCacheTestCase;

class ArrayCacheTest extends MemoryCacheTestCase
{
	protected function getInstance()
	{
		$this->cache = new ArrayCache('localhost');
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testTTL(): void
	{
		// Array Cache doesn't support TTL
		self::markTestSkipped("Array Cache doesn't support TTL");
	}

	public function testGetStats(): void
	{
		self::assertEmpty($this->cache->getStats());
	}
}
