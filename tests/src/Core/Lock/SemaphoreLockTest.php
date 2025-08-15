<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\SemaphoreLock;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Test\LockTestCase;
use Mockery;
use Mockery\MockInterface;

class SemaphoreLockTest extends LockTestCase
{
	protected function setUp(): void
	{
		/** @var MockInterface|Dice $dice */
		$dice = Mockery::mock(Dice::class)->makePartial();

		$app = Mockery::mock(App::class);
		$app->shouldReceive('getHostname')->andReturn('friendica.local');
		$dice->shouldReceive('create')->with(App::class)->andReturn($app);

		$configCache = new Cache(['system' => ['temppath' => '/tmp']]);
		$configMock  = new ReadOnlyFileConfig($configCache);
		$dice->shouldReceive('create')->with(IManageConfigValues::class)->andReturn($configMock);

		// @todo Because "get_temppath()" is using static methods, we have to initialize the BaseObject
		DI::init($dice, true);

		parent::setUp();
	}

	protected function getInstance(): ICanLock
	{
		return new SemaphoreLock();
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testLockTTL()
	{
		self::markTestSkipped("Semaphore doesn't work with TTL");
	}

	/**
	 * Test if semaphore locking works even when trying to release locks, where the file exists
	 * but it shouldn't harm locking
	 */
	public function testMissingFileNotOverriding()
	{
		$file = System::getTempPath() . '/test.sem';
		touch($file);

		self::assertTrue(file_exists($file));
		self::assertFalse($this->instance->release('test', false));
		self::assertTrue(file_exists($file));
	}

	/**
	 * Test overriding semaphore release with already set semaphore
	 * This test proves that semaphore locks cannot get released by other instances except themselves
	 *
	 * Check for Bug https://github.com/friendica/friendica/issues/7298#issuecomment-521996540
	 *
	 * @see https://github.com/friendica/friendica/issues/7298#issuecomment-521996540
	 */
	public function testMissingFileOverriding()
	{
		$file = System::getTempPath() . '/test.sem';
		touch($file);

		self::assertTrue(file_exists($file));
		self::assertFalse($this->instance->release('test', true));
		self::assertTrue(file_exists($file));
	}

	/**
	 * Test acquire lock even the semaphore file exists, but isn't used
	 */
	public function testOverrideSemFile()
	{
		$file = System::getTempPath() . '/test.sem';
		touch($file);

		self::assertTrue(file_exists($file));
		self::assertTrue($this->instance->acquire('test'));
		self::assertTrue($this->instance->isLocked('test'));
		self::assertTrue($this->instance->release('test'));
	}
}
