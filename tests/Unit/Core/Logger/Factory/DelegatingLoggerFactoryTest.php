<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger\Factory;

use Exception;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Factory\DelegatingLoggerFactory;
use Friendica\Core\Logger\Factory\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class DelegatingLoggerFactoryTest extends TestCase
{
	public function testCreateLoggerReturnsPsrLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logger_config', null, 'test'],
		]);

		$factory = new DelegatingLoggerFactory($config);

		$factory->registerFactory('test', $this->createStub(LoggerFactory::class));

		$this->assertInstanceOf(
			LoggerInterface::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)
		);
	}

	public function testCreateLoggerWithoutRegisteredFactoryReturnsNullLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logger_config', null, 'not-existing-factory'],
		]);

		$factory = new DelegatingLoggerFactory($config);

		$this->assertInstanceOf(
			NullLogger::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)
		);
	}

	public function testCreateLoggerWithExceptionThrowingFactoryReturnsNullLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logger_config', null, 'test'],
		]);

		$factory = new DelegatingLoggerFactory($config);

		$brokenFactory = $this->createStub(LoggerFactory::class);
		$brokenFactory->method('createLogger')->willThrowException(new Exception());

		$factory->registerFactory('test', $brokenFactory);

		$this->assertInstanceOf(
			NullLogger::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)
		);
	}
}
