<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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
	private string $errorLog = '';

	public function testCreateLoggerReturnsPsrLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logger_config', null, 'test'],
		]);

		$factory = new DelegatingLoggerFactory($config);

		$factory->registerFactory('test', $this->createStub(LoggerFactory::class));

		$this->assertInstanceOf( // @phpstan-ignore method.alreadyNarrowedType
			LoggerInterface::class,
			$factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT),
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
			$this->captureErrorLog(fn (): \Psr\Log\LoggerInterface => $factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)),
		);

		$this->assertStringContainsString(
			'Friendica: logging is disabled, no log entries will be written.',
			$this->errorLog,
		);
		$this->assertStringContainsString('"system.logger_config" = "not-existing-factory"', $this->errorLog);
	}

	public function testCreateLoggerWithExceptionThrowingFactoryReturnsNullLogger(): void
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnMap([
			['system', 'logger_config', null, 'test'],
		]);

		$factory = new DelegatingLoggerFactory($config);

		$brokenFactory = $this->createStub(LoggerFactory::class);
		$brokenFactory->method('createLogger')->willThrowException(new Exception('"php://stdout" is not a valid logfile.'));

		$factory->registerFactory('test', $brokenFactory);

		$this->assertInstanceOf(
			NullLogger::class,
			$this->captureErrorLog(fn (): \Psr\Log\LoggerInterface => $factory->createLogger(LogLevel::DEBUG, LogChannel::DEFAULT)),
		);

		$this->assertStringContainsString(
			'Friendica: logging is disabled, no log entries will be written.',
			$this->errorLog,
		);
		$this->assertStringContainsString('"php://stdout" is not a valid logfile.', $this->errorLog);
	}

	/**
	 * Runs $callback with error_log() redirected into a temporary file.
	 * The written content ends up in $this->errorLog.
	 */
	private function captureErrorLog(callable $callback): mixed
	{
		$file     = tempnam(sys_get_temp_dir(), 'friendica-error-log-');
		$previous = ini_get('error_log');

		ini_set('error_log', $file);

		try {
			return $callback();
		} finally {
			ini_set('error_log', $previous === false ? '' : $previous);

			$this->errorLog = (string) file_get_contents($file);

			unlink($file);
		}
	}
}
