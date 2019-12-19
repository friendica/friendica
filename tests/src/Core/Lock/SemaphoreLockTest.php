<?php

namespace Friendica\Test\src\Core\Lock;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Config\JitConfiguration;
use Friendica\Core\Lock\SemaphoreLock;
use Friendica\DI;
use Mockery\MockInterface;

class SemaphoreLockTest extends LockTest
{
	public function setUp()
	{
		/** @var MockInterface|Dice $dice */
		$dice = \Mockery::mock(Dice::class)->makePartial();

		$app = \Mockery::mock(App::class);
		$app->shouldReceive('getHostname')->andReturn('friendica.local');
		$dice->shouldReceive('create')->with(App::class, [])->andReturn($app);

		$configMock = \Mockery::mock(JitConfiguration::class);
		$configMock
			->shouldReceive('get')
			->with('system', 'temppath', NULL, false)
			->andReturn('/tmp/');
		$dice->shouldReceive('create')->with(IConfiguration::class, [])->andReturn($configMock);

		// @todo Because "get_temppath()" is using static methods, we have to initialize the BaseObject
		DI::init($dice);

		parent::setUp();
	}

	protected function getInstance()
	{
		return new SemaphoreLock();
	}

	function testLockTTL()
	{
		// Semaphore doesn't work with TTL
		return true;
	}

	/**
	 * Test if semaphore locking works even when trying to release locks, where the file exists
	 * but it shouldn't harm locking
	 */
	public function testMissingFileNotOverriding()
	{
		$file = get_temppath() . '/test.sem';
		touch($file);

		$this->assertTrue(file_exists($file));
		$this->assertFalse($this->instance->releaseLock('test', false));
		$this->assertTrue(file_exists($file));
	}

	/**
	 * Test overriding semaphore release with already set semaphore
	 * This test proves that semaphore locks cannot get released by other instances except themselves
	 *
	 * Check for Bug https://github.com/friendica/friendica/issues/7298#issuecomment-521996540
	 * @see https://github.com/friendica/friendica/issues/7298#issuecomment-521996540
	 */
	public function testMissingFileOverriding()
	{
		$file = get_temppath() . '/test.sem';
		touch($file);

		$this->assertTrue(file_exists($file));
		$this->assertFalse($this->instance->releaseLock('test', true));
		$this->assertTrue(file_exists($file));
	}

	/**
	 * Test acquire lock even the semaphore file exists, but isn't used
	 */
	public function testOverrideSemFile()
	{
		$file = get_temppath() . '/test.sem';
		touch($file);

		$this->assertTrue(file_exists($file));
		$this->assertTrue($this->instance->acquireLock('test'));
		$this->assertTrue($this->instance->isLocked('test'));
		$this->assertTrue($this->instance->releaseLock('test'));
	}
}
