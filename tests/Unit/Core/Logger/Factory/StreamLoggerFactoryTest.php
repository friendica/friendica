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
use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Factory\StreamLoggerFactory;
use Friendica\Core\Logger\Util\FileSystemUtil;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class StreamLoggerFactoryTest extends TestCase
{
	public function testCreateLoggerReturnsPsrLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, dirname(__DIR__, 4) . '/datasets/log/empty.friendica.log.txt'],
		]);

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$this->createStub(FileSystemUtil::class),
		);

		$this->assertInstanceOf(
			LoggerInterface::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)
		);
	}

	public function testCreateLoggerWithInvalidLogfileThrowsException(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, dirname(__DIR__, 1) . '/not-existing-logfile.txt'],
		]);

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$this->createStub(FileSystemUtil::class),
		);

		$this->expectException(LoggerArgumentException::class);
		$this->expectExceptionMessage('tests/Unit/Core/Logger/not-existing-logfile.txt" is not a valid logfile.');

		$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT);
	}

	public function testCreateLoggerWithInvalidLoglevelThrowsException(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, dirname(__DIR__, 4) . '/datasets/log/empty.friendica.log.txt'],
		]);

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$this->createStub(FileSystemUtil::class),
		);

		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessage('The log level "unsupported-loglevel" is not supported by "Friendica\Core\Logger\Type\StreamLogger".');

		$factory->createLogger('unsupported-loglevel', LogChannel::DEFAULT);
	}
}
