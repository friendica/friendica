<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Test\LoggerDataTrait;
use Friendica\Test\MockedTestCase;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ProfilerLoggerTest extends MockedTestCase
{
	use LoggerDataTrait;

	/**
	 * @var LoggerInterface|MockInterface
	 */
	private $logger;
	/**
	 * @var Profiler|MockInterface
	 */
	private $profiler;

	protected function setUp(): void
	{
		parent::setUp();

		$this->logger   = \Mockery::mock(LoggerInterface::class);
		$this->profiler = \Mockery::mock(Profiler::class);
	}

	/**
	 * Test if the profiler is profiling data
	 * @dataProvider dataTests
	 * @doesNotPerformAssertions
	 */
	public function testProfiling($function, $message, array $context)
	{
		$logger = new ProfilerLogger($this->logger, $this->profiler);

		$this->logger->shouldReceive($function)->with($message, $context)->once();
		$this->profiler->shouldReceive('startRecording')->with('file')->once();
		$this->profiler->shouldReceive('stopRecording');
		$this->profiler->shouldReceive('saveTimestamp');
		$logger->$function($message, $context);
	}

	/**
	 * Test the log() function
	 * @doesNotPerformAssertions
	 */
	public function testProfilingLog()
	{
		$logger = new ProfilerLogger($this->logger, $this->profiler);

		$this->logger->shouldReceive('log')->with(LogLevel::WARNING, 'test', ['a' => 'context'])->once();
		$this->profiler->shouldReceive('startRecording')->with('file')->once();
		$this->profiler->shouldReceive('stopRecording');
		$this->profiler->shouldReceive('saveTimestamp');

		$logger->log(LogLevel::WARNING, 'test', ['a' => 'context']);
	}
}
