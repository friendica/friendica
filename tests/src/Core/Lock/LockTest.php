<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Test\MockedTest;

abstract class LockTest extends MockedTest
{
	/**
	 * @var int Start time of the mock (used for time operations)
	 */
	protected $startTime = 1417011228;

	/**
	 * @var ICanLock
	 */
	protected $instance;

	abstract protected function getInstance();

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
