<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Core\Cache;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Cache\Type\ProfilerCacheDecorator;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MemoryCacheTestCase;
use Friendica\Util\Profiler;

class ProfilerCacheDecoratorTest extends MemoryCacheTestCase
{
	protected function getInstance()
	{
		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')->with('system', 'profiler')->once()->andReturn(false);
		$config->shouldReceive('get')->with('rendertime', 'callstack')->once()->andReturn(false);

		$this->cache = new ProfilerCacheDecorator(new ArrayCache('localhost'), new Profiler($config));
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testTTL()
	{
		// Array Cache doesn't support TTL
		self::markTestSkipped("Array Cache doesn't support TTL");
		return true;
	}

	/**
	 * @small
	 */
	public function testGetStats()
	{
		self::assertEmpty($this->cache->getStats());
	}

	public function testGetName()
	{
		self::assertStringEndsWith(' (with profiler)', $this->instance->getName());
	}
}
