<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Util\Introspection;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

abstract class LoggerTestCase extends MockedTestCase
{
	use LoggerDataTrait;

	public const LOGLINE = '/.* \[.*]: .* {.*\"file\":\".*\".*,.*\"line\":\d*,.*\"function\":\".*\".*,.*\"uid\":\".*\".*}/';

	public const FILE = 'test';
	public const LINE = 666;
	public const FUNC = 'myfunction';

	/**
	 * @var Introspection|MockInterface
	 */
	protected $introspection;
	/**
	 * @var IManageConfigValues|MockInterface
	 */
	protected $config;

	/**
	 * Returns the content of the current logger instance
	 *
	 * @return string
	 */
	abstract protected function getContent();

	/**
	 * Returns the current logger instance
	 *
	 * @param string $level the default loglevel
	 *
	 * @return LoggerInterface
	 */
	abstract protected function getInstance($level = LogLevel::DEBUG);

	protected function setUp(): void
	{
		parent::setUp();

		$this->introspection = \Mockery::mock(Introspection::class);
		$this->introspection->shouldReceive('getRecord')->andReturn([
			'file'     => self::FILE,
			'line'     => self::LINE,
			'function' => self::FUNC,
		]);

		$this->config = \Mockery::mock(IManageConfigValues::class);
	}

	public function assertLogline($string)
	{
		self::assertMatchesRegularExpression(self::LOGLINE, $string);
	}

	public function assertLoglineNums($assertNum, $string)
	{
		self::assertEquals($assertNum, preg_match_all(self::LOGLINE, (string) $string));
	}

	/**
	 * Test if the logger works correctly
	 */
	public function testNormal(): void
	{
		$logger = $this->getInstance();
		$logger->emergency('working!');
		$logger->alert('working too!');
		$logger->debug('and now?');
		$logger->notice('message', ['an' => 'context']);

		$text = $this->getContent();
		self::assertLogline($text);
		self::assertLoglineNums(4, $text);
	}

	/**
	 * Test if a log entry is correctly interpolated
	 */
	public function testPsrInterpolate(): void
	{
		$logger = $this->getInstance();

		$logger->emergency('A {psr} test', ['psr' => 'working']);
		$logger->alert('An {array} test', ['array' => ['it', 'is', 'working']]);
		$text = $this->getContent();
		self::assertStringContainsString('A working test', $text);
		self::assertStringContainsString('An ["it","is","working"] test', $text);
	}

	/**
	 * Test if a log entry contains all necessary information
	 */
	public function testContainsInformation(): void
	{
		$logger = $this->getInstance();
		$logger->emergency('A test');

		$text = $this->getContent();
		self::assertStringContainsString('"file":"' . self::FILE . '"', $text);
		self::assertStringContainsString('"line":' . self::LINE, $text);
		self::assertStringContainsString('"function":"' . self::FUNC . '"', $text);
	}

	/**
	 * Test if the minimum level is working
	 */
	public function testMinimumLevel(): void
	{
		$logger = $this->getInstance(LogLevel::NOTICE);

		$logger->emergency('working');
		$logger->alert('working');
		$logger->error('working');
		$logger->warning('working');
		$logger->notice('working');
		$logger->info('not working');
		$logger->debug('not working');

		$text = $this->getContent();

		self::assertLoglineNums(5, $text);
	}

	/**
	 * Test with different logging data
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testDifferentTypes($function, $message, array $context): void
	{
		$logger = $this->getInstance();
		$logger->$function($message, $context);

		$text = $this->getContent();

		self::assertLogline($text);

		self::assertStringContainsString(@json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $text);
	}

	/**
	 * Test a message with an exception
	 */
	public function testExceptionHandling(): void
	{
		$e         = new \Exception("Test String", 123);
		$eFollowUp = new \Exception("FollowUp", 456, $e);

		$assertion = $eFollowUp->__toString();

		$logger = $this->getInstance();
		$logger->alert('test', ['e' => $eFollowUp]);
		$text = $this->getContent();

		self::assertLogline($text);

		self::assertStringContainsString(@json_encode($assertion, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->getContent());
	}

	public function testNoObjectHandling(): void
	{
		$logger = $this->getInstance();
		$logger->alert('test', ['e' => ['test' => 'test']]);
		$text = $this->getContent();

		self::assertLogline($text);

		self::assertStringContainsString('test', $this->getContent());
	}
}
