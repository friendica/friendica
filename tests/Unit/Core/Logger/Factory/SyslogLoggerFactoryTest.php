<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Factory\SyslogLoggerFactory;
use Friendica\Core\Logger\Type\SyslogLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class SyslogLoggerFactoryTest extends TestCase
{
	public function testCreateLoggerReturnsPsrLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'syslog_flags', null, SyslogLogger::DEFAULT_FLAGS],
			['system', 'syslog_facility', null, SyslogLogger::DEFAULT_FACILITY],
		]);

		$factory = new SyslogLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
		);

		$this->assertInstanceOf(
			LoggerInterface::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)
		);
	}

	public function testCreateLoggerWithInvalidLoglevelThrowsException(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'syslog_flags', null, SyslogLogger::DEFAULT_FLAGS],
			['system', 'syslog_facility', null, SyslogLogger::DEFAULT_FACILITY],
		]);

		$factory = new SyslogLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
		);

		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessage('The log level "unsupported-loglevel" is not supported by "Friendica\Core\Logger\Type\SyslogLogger".');

		$factory->createLogger('unsupported-loglevel', LogChannel::DEFAULT);
	}
}
