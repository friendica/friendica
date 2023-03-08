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

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\System;
use Friendica\DI;
use Mockery;
use Mockery\MockInterface;

class SemaphoreLockTest extends LockTest
{
	protected function setUp(): void
	{
		/** @var MockInterface|Dice $dice */
		$dice = Mockery::mock(Dice::class)->makePartial();

		$app = Mockery::mock(App::class);
		$app->shouldReceive('getHostname')->andReturn('friendica.local');
		$dice->shouldReceive('create')->with(App::class)->andReturn($app);

		$configCache = new Cache(['system' => ['temppath' => '/tmp']]);
		$configMock = new ReadOnlyFileConfig($configCache);
		$dice->shouldReceive('create')->with(IManageConfigValues::class)->andReturn($configMock);

		// @todo Because "get_temppath()" is using static methods, we have to initialize the BaseObject
		DI::init($dice, true);

		parent::setUp();
	}

	protected function getInstance()
	{
		return new \Friendica\Core\Lock\Type\SemaphoreLock();
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
