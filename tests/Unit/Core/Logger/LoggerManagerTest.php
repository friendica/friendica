<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Factory\LoggerFactory;
use Friendica\Core\Logger\LoggerManager;
use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Core\Logger\Type\WorkerLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerManagerTest extends TestCase
{
	/**
	 * Clean the private static properties
	 *
	 * @see LoggerManager::$logger
	 * @see LoggerManager::$logChannel
	 */
	protected function tearDown(): void
	{
		$reflectionProperty = new \ReflectionProperty(LoggerManager::class, 'logger');
		$reflectionProperty->setValue(null, null);

		$reflectionProperty = new \ReflectionProperty(LoggerManager::class, 'logChannel');
		$reflectionProperty->setValue(null, LogChannel::DEFAULT);
	}

	public function testGetLoggerReturnsPsrLogger(): void
	{
		$factory = new LoggerManager(
			$this->createStub(IManageConfigValues::class),
			$this->createStub(LoggerFactory::class),
		);

		$this->assertInstanceOf(LoggerInterface::class, $factory->getLogger()); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetLoggerReturnsSameObject(): void
	{
		$factory = new LoggerManager(
			$this->createStub(IManageConfigValues::class),
			$this->createStub(LoggerFactory::class),
		);

		$this->assertSame($factory->getLogger(), $factory->getLogger());
	}

	public function testGetLoggerWithDebugDisabledReturnsNullLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
		]);

		$factory = new LoggerManager(
			$config,
			$this->createStub(LoggerFactory::class),
		);

		$this->assertInstanceOf(NullLogger::class, $factory->getLogger());
	}

	public function testGetLoggerWithProfilerEnabledReturnsProfilerLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', false, false],
			['system', 'profiling', false, true],
		]);

		$factory = new LoggerManager(
			$config,
			$this->createStub(LoggerFactory::class),
		);

		$this->assertInstanceOf(ProfilerLogger::class, $factory->getLogger());
	}

	public function testChangeLogChannelReturnsDifferentLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
			['system', 'profiling', null, true],
		]);

		$factory = new LoggerManager(
			$config,
			$this->createStub(LoggerFactory::class),
		);

		$logger1 = $factory->getLogger();

		$factory->changeLogChannel(LogChannel::CONSOLE);

		$this->assertNotSame($logger1, $factory->getLogger());
	}

	public function testChangeLogChannelToWorkerReturnsWorkerLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'debugging', null, false],
			['system', 'profiling', null, true],
		]);

		$factory = new LoggerManager(
			$config,
			$this->createStub(LoggerFactory::class),
		);

		$factory->changeLogChannel(LogChannel::WORKER);

		$this->assertInstanceOf(WorkerLogger::class, $factory->getLogger());
	}
}
