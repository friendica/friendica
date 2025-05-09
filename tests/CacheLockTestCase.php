<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Lock\Capability\ICanLock;

abstract class CacheLockTestCase extends LockTestCase
{
	abstract protected function getCache(): ICanCacheInMemory;

	abstract protected function getInstance(): ICanLock;

	/**
	 * Test if the getStats() result is identically to the getCacheStats()
	 */
	public function testGetStats()
	{
		self::assertSame(array_keys($this->getCache()->getStats()), array_keys($this->instance->getCacheStats()));
	}
}
