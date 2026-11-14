<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LoggerUnusableException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Factory\StreamLoggerFactory;
use Friendica\Core\Logger\Type\StreamLogger;
use Friendica\Core\Logger\Util\FileSystem;
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
			['system', 'logfile', null, dirname(__DIR__, 4) . '/Fixtures/log/empty.friendica.log.txt'],
		]);

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$this->createStub(FileSystemUtil::class),
		);

		$this->assertInstanceOf( // @phpstan-ignore method.alreadyNarrowedType
			LoggerInterface::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT),
		);
	}

	/**
	 * A stream wrapper URL is a valid log target and must reach the filesystem util unchanged.
	 * file_exists() is false for "php://stdout", because php:// implements no url_stat() handler.
	 */
	public function testCreateLoggerAcceptsStreamWrapperUrlAsLogfile(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, 'php://memory'],
		]);

		$fileSystem = $this->createMock(FileSystemUtil::class);
		$fileSystem->expects($this->once())
			->method('createStream')
			->with('php://memory')
			->willReturn(fopen('php://memory', 'ab'));

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$fileSystem,
		);

		$this->assertInstanceOf(
			StreamLogger::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT),
		);
	}

	/**
	 * A logfile that does not exist yet is valid: createStream() opens it with fopen(..., 'ab'),
	 * which creates the file.
	 */
	public function testCreateLoggerAcceptsNotYetExistingLogfile(): void
	{
		$logfile = dirname(__DIR__, 1) . '/not-existing-logfile.txt';

		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, $logfile],
		]);

		$fileSystem = $this->createMock(FileSystemUtil::class);
		$fileSystem->expects($this->once())
			->method('createStream')
			->with($logfile)
			->willReturn(fopen('php://memory', 'ab'));

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$fileSystem,
		);

		$this->assertInstanceOf(
			StreamLogger::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT),
		);
	}

	/**
	 * Guards the fix against the real FileSystem instead of a test double.
	 * Both a not yet existing logfile and the write through the created stream are covered.
	 */
	public function testCreateLoggerWritesToNotYetExistingLogfileWithRealFileSystem(): void
	{
		$logfile = sys_get_temp_dir() . '/friendica-stream-logger-' . uniqid() . '.log';

		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, $logfile],
		]);

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			new FileSystem(),
		);

		try {
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)->notice('a logged line');

			$this->assertStringContainsString('a logged line', (string) file_get_contents($logfile));
		} finally {
			if (file_exists($logfile)) {
				unlink($logfile);
			}
		}
	}

	public function testCreateLoggerWithUnusableLogfileThrowsException(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, '/this/path/cannot/be/opened.log'],
		]);

		$fileSystem = $this->createStub(FileSystemUtil::class);
		$fileSystem->method('createStream')
			->willThrowException(new LoggerUnusableException('Permission denied'));

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$fileSystem,
		);

		$this->expectException(LoggerArgumentException::class);
		$this->expectExceptionMessage('"/this/path/cannot/be/opened.log" is not a valid logfile.');

		$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT);
	}

	public function testCreateLoggerWithEmptyLogfileThrowsException(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, ''],
		]);

		$factory = new StreamLoggerFactory(
			$config,
			$this->createStub(IHaveCallIntrospections::class),
			$this->createStub(FileSystemUtil::class),
		);

		$this->expectException(LoggerArgumentException::class);
		$this->expectExceptionMessage('The config value "system.logfile" is empty, there is nothing to log into.');

		$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT);
	}

	public function testCreateLoggerWithInvalidLoglevelThrowsException(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logfile', null, dirname(__DIR__, 4) . '/Fixtures/log/empty.friendica.log.txt'],
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
