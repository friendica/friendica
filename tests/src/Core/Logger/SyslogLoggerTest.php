<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Type\SyslogLogger;
use Friendica\Test\LoggerTestCase;
use Psr\Log\LogLevel;

class SyslogLoggerTest extends LoggerTestCase
{
	/**
	 * @var SyslogLoggerWrapper
	 */
	private $logger;

	protected function setUp(): void
	{
		parent::setUp();

		$this->introspection->shouldReceive('addClasses')->with([SyslogLogger::class]);
		$this->config->shouldReceive('get')->with('system', 'syslog_flags')->andReturn(SyslogLogger::DEFAULT_FLAGS)
					 ->once();
		$this->config->shouldReceive('get')->with('system', 'syslog_facility')
					 ->andReturn(SyslogLogger::DEFAULT_FACILITY)->once();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getContent()
	{
		return $this->logger->getContent();
	}

	/**
	 * {@inheritdoc}
	 * @return SyslogLoggerWrapper
	 */
	protected function getInstance($level = LogLevel::DEBUG)
	{
		$this->config->shouldReceive('get')->with('system', 'loglevel')->andReturn($level);

		$loggerFactory = new SyslogLoggerFactoryWrapper($this->introspection, 'test');
		$this->logger  = $loggerFactory->create($this->config);

		return $this->logger;
	}


	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongMinimumLevel(): void
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");

		$logger = $this->getInstance('NOPE');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongLogLevel(): void
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");

		$logger = $this->getInstance();

		$logger->log('NOPE', 'a test');
	}

	/**
	 * Test the close() method
	 */
	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testClose(): void
	{
		$logger = $this->getInstance();
		$logger->emergency('test');
		$logger->close();
		// Reopened itself
		$logger->emergency('test');
	}
}
