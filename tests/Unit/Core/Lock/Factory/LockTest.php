<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Lock\Factory;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Cache\Factory\Cache;
use Friendica\Core\Cache\Type\APCuCache;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Lock\Factory\Lock;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Core\Lock\Type\DatabaseLock;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Separate processes, because php-mock can't mock functions which were already called in the namespace (e.g. by other lock tests).
 */
#[RunTestsInSeparateProcesses]
class LockTest extends TestCase
{
	use PHPMock;

	protected function setUp(): void
	{
		parent::setUp();

		(new \ReflectionProperty(Cache::class, 'unavailable'))->setValue(null, []);

		// No semaphores, so the auto driver has to choose between cache and database locking
		$this->getFunctionMock('Friendica\Core\Lock\Factory', 'function_exists')
			->expects(self::any())
			->willReturnCallback(fn (string $function): bool => $function !== 'sem_get' && \function_exists($function));
	}

	private function config(): IManageConfigValues
	{
		$values = ['lock_driver' => APCuCache::NAME, 'cache_driver' => APCuCache::NAME];

		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnCallback(fn (string $cat, string $key, mixed $default = null): mixed => $values[$key] ?? $default);

		return $config;
	}

	private function lockFactory(ICanCreateInstances $instanceCreator): Lock
	{
		$config = $this->config();
		$logger = new NullLogger();

		return new Lock(new Cache($instanceCreator, $config, $this->createStub(Profiler::class), $logger), $config, $this->createStub(Database::class), $logger);
	}

	public function testAvailableApcuIsUsedForLocking(): void
	{
		$instanceCreator = $this->createStub(ICanCreateInstances::class);
		$instanceCreator->method('create')->willReturn($this->createStub(ICanCacheInMemory::class));

		self::assertInstanceOf(CacheLock::class, $this->lockFactory($instanceCreator)->create());
	}

	public function testUnavailableApcuFallsBackToDatabaseLock(): void
	{
		$default         = $this->createStub(ICanCache::class);
		$halfConstructed = $this->createStub(ICanCacheInMemory::class);
		$calls           = 0;

		// Behaves like Dice with shared instances: the second call returns the instance whose constructor threw
		$instanceCreator = $this->createStub(ICanCreateInstances::class);
		$instanceCreator->method('create')->willReturnCallback(function (string $class, string $strategy) use ($default, $halfConstructed, &$calls): object {
			if ($strategy === DatabaseCache::NAME) {
				return $default;
			}
			if (++$calls === 1) {
				throw new InvalidCacheDriverException('APCu is not available.');
			}
			return $halfConstructed;
		});

		self::assertInstanceOf(DatabaseLock::class, $this->lockFactory($instanceCreator)->create());
		self::assertSame(1, $calls);
	}
}
