<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Friendica\Core\Lock\Capability\ICanLock;

abstract class LockTestCase extends MockedTestCase
{
	/**
	 * Start time of the mock (used for time operations)
	 */
	protected int $startTime = 1417011228;
	protected ICanLock $instance;

	abstract protected function getInstance(): ICanLock;


	protected function setUp(): void
	{
		parent::setUp();

		$this->instance = $this->getInstance();
		$this->instance->releaseAll(true);
	}

	protected function tearDown(): void
	{
		$this->instance->releaseAll(true);
		parent::tearDown();
	}

	/**
	 * @small
	 */
	public function testLock()
	{
		self::assertFalse($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->acquire('foo', 1));
		self::assertTrue($this->instance->isLocked('foo'));
		self::assertFalse($this->instance->isLocked('bar'));
	}

	/**
	 * @small
	 */
	public function testDoubleLock()
	{
		self::assertFalse($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->acquire('foo', 1));
		self::assertTrue($this->instance->isLocked('foo'));
		// We already locked it
		self::assertTrue($this->instance->acquire('foo', 1));
	}

	/**
	 * @small
	 */
	public function testReleaseLock()
	{
		self::assertFalse($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->acquire('foo', 1));
		self::assertTrue($this->instance->isLocked('foo'));
		$this->instance->release('foo');
		self::assertFalse($this->instance->isLocked('foo'));
	}

	/**
	 * @small
	 */
	public function testReleaseAll()
	{
		self::assertTrue($this->instance->acquire('foo', 1));
		self::assertTrue($this->instance->acquire('bar', 1));
		self::assertTrue($this->instance->acquire('nice', 1));

		self::assertTrue($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->isLocked('bar'));
		self::assertTrue($this->instance->isLocked('nice'));

		self::assertTrue($this->instance->releaseAll());

		self::assertFalse($this->instance->isLocked('foo'));
		self::assertFalse($this->instance->isLocked('bar'));
		self::assertFalse($this->instance->isLocked('nice'));
	}

	/**
	 * @small
	 */
	public function testReleaseAfterUnlock()
	{
		self::assertFalse($this->instance->isLocked('foo'));
		self::assertFalse($this->instance->isLocked('bar'));
		self::assertFalse($this->instance->isLocked('nice'));
		self::assertTrue($this->instance->acquire('foo', 1));
		self::assertTrue($this->instance->acquire('bar', 1));
		self::assertTrue($this->instance->acquire('nice', 1));

		self::assertTrue($this->instance->release('foo'));

		self::assertFalse($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->isLocked('bar'));
		self::assertTrue($this->instance->isLocked('nice'));

		self::assertTrue($this->instance->releaseAll());

		self::assertFalse($this->instance->isLocked('bar'));
		self::assertFalse($this->instance->isLocked('nice'));
	}

	/**
	 * @small
	 */
	public function testReleaseWitTTL()
	{
		self::assertFalse($this->instance->isLocked('test'));
		self::assertTrue($this->instance->acquire('test', 1, 10));
		self::assertTrue($this->instance->isLocked('test'));
		self::assertTrue($this->instance->release('test'));
		self::assertFalse($this->instance->isLocked('test'));
	}

	/**
	 * @small
	 */
	public function testGetLocks()
	{
		self::assertTrue($this->instance->acquire('foo', 1));
		self::assertTrue($this->instance->acquire('bar', 1));
		self::assertTrue($this->instance->acquire('nice', 1));

		self::assertTrue($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->isLocked('bar'));
		self::assertTrue($this->instance->isLocked('nice'));

		$locks = $this->instance->getLocks();

		self::assertContains('foo', $locks);
		self::assertContains('bar', $locks);
		self::assertContains('nice', $locks);
	}

	/**
	 * @small
	 */
	public function testGetLocksWithPrefix()
	{
		self::assertTrue($this->instance->acquire('foo', 1));
		self::assertTrue($this->instance->acquire('test1', 1));
		self::assertTrue($this->instance->acquire('test2', 1));

		self::assertTrue($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->isLocked('test1'));
		self::assertTrue($this->instance->isLocked('test2'));

		$locks = $this->instance->getLocks('test');

		self::assertContains('test1', $locks);
		self::assertContains('test2', $locks);
		self::assertNotContains('foo', $locks);
	}

	/**
	 * @medium
	 */
	public function testLockTTL()
	{
		static::markTestSkipped('taking too much time without mocking');

		self::assertFalse($this->instance->isLocked('foo'));
		self::assertFalse($this->instance->isLocked('bar'));

		// TODO [nupplaphil] - Because of the Datetime-Utils for the database, we have to wait a FULL second between the checks to invalidate the db-locks/cache
		self::assertTrue($this->instance->acquire('foo', 2, 1));
		self::assertTrue($this->instance->acquire('bar', 2, 3));

		self::assertTrue($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->isLocked('bar'));

		sleep(2);

		self::assertFalse($this->instance->isLocked('foo'));
		self::assertTrue($this->instance->isLocked('bar'));

		sleep(2);

		self::assertFalse($this->instance->isLocked('foo'));
		self::assertFalse($this->instance->isLocked('bar'));
	}

	/**
	 * Test if releasing a non-existing lock doesn't throw errors
	 */
	public function testReleaseLockWithoutLock()
	{
		self::assertFalse($this->instance->isLocked('wrongLock'));
		self::assertFalse($this->instance->release('wrongLock'));
	}


}
