<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Cache\Factory;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Cache\Factory\Cache;
use Friendica\Core\Cache\Type\APCuCache;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Core\Cache\Type\ProfilerCacheDecorator;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CacheTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// The list of unavailable drivers is shared by all factory instances of one process
		(new \ReflectionProperty(Cache::class, 'unavailable'))->setValue(null, []);
	}

	/**
	 * @param array<string, mixed> $config ['key' => value] of the "system" category
	 */
	private function factory(ICanCreateInstances $instanceCreator, array $config = [], ?LoggerInterface $logger = null): Cache
	{
		$configMock = $this->createStub(IManageConfigValues::class);
		$configMock->method('get')->willReturnCallback(fn (string $cat, string $key, mixed $default = null): mixed => $config[$key] ?? $default);

		return new Cache($instanceCreator, $configMock, $this->createStub(Profiler::class), $logger ?? new NullLogger());
	}

	/**
	 * Behaves like Dice with shared instances: a driver whose constructor threw is returned half-constructed on the next call.
	 *
	 * @param array<string, int> $calls Counts the create() calls per strategy
	 */
	private function diceLikeInstanceCreator(ICanCache $default, ICanCacheInMemory $halfConstructed, array &$calls = []): ICanCreateInstances
	{
		$instanceCreator = $this->createStub(ICanCreateInstances::class);
		$instanceCreator->method('create')->willReturnCallback(function (string $class, string $strategy) use ($default, $halfConstructed, &$calls): object {
			$calls[$strategy] = ($calls[$strategy] ?? 0) + 1;

			if ($strategy === DatabaseCache::NAME) {
				return $default;
			}

			if ($calls[$strategy] === 1) {
				throw new InvalidCacheDriverException('APCu is not available.');
			}

			return $halfConstructed;
		});

		return $instanceCreator;
	}

	public function testAvailableDriverIsUsed(): void
	{
		$apcu            = $this->createStub(ICanCacheInMemory::class);
		$instanceCreator = $this->createMock(ICanCreateInstances::class);
		$instanceCreator->expects(self::once())->method('create')->with(ICanCache::class, APCuCache::NAME)->willReturn($apcu);

		self::assertSame($apcu, $this->factory($instanceCreator)->createLocal(APCuCache::NAME));
	}

	public function testUnavailableDriverFallsBackToDefault(): void
	{
		$default = $this->createStub(ICanCache::class);
		$calls   = [];
		$logger  = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())
			->method('warning')
			->with(self::anything(), self::callback(fn (array $context): bool => $context['driver'] === APCuCache::NAME && $context['exception'] instanceof InvalidCacheDriverException));

		$factory = $this->factory($this->diceLikeInstanceCreator($default, $this->createStub(ICanCacheInMemory::class), $calls), [], $logger);

		self::assertSame($default, $factory->createLocal(APCuCache::NAME));
		self::assertSame([APCuCache::NAME => 1, DatabaseCache::NAME => 1], $calls);
	}

	public function testConfiguredUnavailableDriverFallsBackToDefault(): void
	{
		$default = $this->createStub(ICanCache::class);
		$factory = $this->factory($this->diceLikeInstanceCreator($default, $this->createStub(ICanCacheInMemory::class)), ['cache_driver' => APCuCache::NAME, 'distributed_cache_driver' => APCuCache::NAME]);

		self::assertSame($default, $factory->createLocal());
		self::assertSame($default, $factory->createDistributed());
	}

	public function testUnavailableDriverIsNotCreatedTwice(): void
	{
		$default         = $this->createStub(ICanCache::class);
		$calls           = [];
		$instanceCreator = $this->diceLikeInstanceCreator($default, $this->createStub(ICanCacheInMemory::class), $calls);

		$this->factory($instanceCreator)->createLocal(APCuCache::NAME);
		// A second factory instance, like the ones Dice builds for ICanCache, ICanCacheInMemory and the Lock factory
		$cache = $this->factory($instanceCreator)->createLocal(APCuCache::NAME);

		self::assertSame($default, $cache);
		self::assertSame(1, $calls[APCuCache::NAME]);
	}

	public function testFallbackIsWrappedWhenProfiling(): void
	{
		$factory = $this->factory($this->diceLikeInstanceCreator($this->createStub(ICanCache::class), $this->createStub(ICanCacheInMemory::class)), ['profiling' => true]);

		self::assertInstanceOf(ProfilerCacheDecorator::class, $factory->createLocal(APCuCache::NAME));
	}

	public function testUnavailableDefaultDriverIsRethrown(): void
	{
		$instanceCreator = $this->createStub(ICanCreateInstances::class);
		$instanceCreator->method('create')->willThrowException(new InvalidCacheDriverException('broken'));

		$this->expectException(InvalidCacheDriverException::class);

		$this->factory($instanceCreator)->createLocal(DatabaseCache::NAME);
	}
}
