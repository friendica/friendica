<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Test\CacheTestCase;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\CreateDatabaseTrait;
use Friendica\Test\Util\VFSTrait;

class DatabaseCacheTest extends CacheTestCase
{
	use DatabaseTestTrait;
	use CreateDatabaseTrait;
	use VFSTrait;

	protected function setUp(): void
	{
		$this->setUpVfsDir();

		$this->setUpDb();

		parent::setUp();
	}

	protected function getInstance()
	{
		$this->cache = new DatabaseCache('database', $this->getDbInstance());
		return $this->cache;
	}

	protected function tearDown(): void
	{
		$this->cache->clear(false);

		$this->tearDownDb();

		parent::tearDown();
	}

	public function testInfiniteTtlIsStoredAndSurvivesClearingOutdated(): void
	{
		self::assertTrue($this->instance->set('infinite', 'value', Duration::INFINITE));
		self::assertSame('value', $this->instance->get('infinite'));
		self::assertContains('infinite', $this->instance->getAllKeys());

		self::assertTrue($this->instance->clear());
		self::assertSame('value', $this->instance->get('infinite'));
	}
}
