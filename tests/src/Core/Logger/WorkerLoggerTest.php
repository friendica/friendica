<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Type\WorkerLogger;
use Friendica\Test\MockedTestCase;
use Psr\Log\LoggerInterface;

class WorkerLoggerTest extends MockedTestCase
{
	private function assertUid($uid)
	{
		self::assertMatchesRegularExpression('/^[a-zA-Z0-9]{' . WorkerLogger::WORKER_ID_LENGTH . '}+$/', $uid);
	}

	public function dataTest()
	{
		return [
			'info' => [
				'func'    => 'info',
				'msg'     => 'the alert',
				'context' => [],
			],
			'alert' => [
				'func'    => 'alert',
				'msg'     => 'another alert',
				'context' => ['test' => 'it'],
			],
			'critical' => [
				'func'    => 'critical',
				'msg'     => 'Critical msg used',
				'context' => ['test' => 'it', 'more' => 0.24545],
			],
			'error' => [
				'func'    => 'error',
				'msg'     => 21345623,
				'context' => ['test' => 'it', 'yet' => true],
			],
			'warning' => [
				'func'    => 'warning',
				'msg'     => 'another alert' . 123523 . 324.54534 . 'test',
				'context' => ['test' => 'it', 2 => 'nope'],
			],
			'notice' => [
				'func'    => 'notice',
				'msg'     => 'Notice' . ' alert' . true . 'with' . '\'strange\'' . 1.24. 'behavior',
				'context' => ['test' => 'it'],
			],
			'debug' => [
				'func'    => 'debug',
				'msg'     => 'at last a debug',
				'context' => ['test' => 'it'],
			],
		];
	}

	/**
	 * Test the WorkerLogger with different log calls
	 * @dataProvider dataTest
	 */
	public function testEmergency($func, $msg, $context = [])
	{
		$logger                    = \Mockery::mock(LoggerInterface::class);
		$workLogger                = new WorkerLogger($logger);
		$testContext               = $context;
		$testContext['worker_id']  = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = '';
		self::assertUid($testContext['worker_id']);
		$logger
			->shouldReceive($func)
			->with($msg, $testContext)
			->once();
		$workLogger->$func($msg, $context);
	}

	/**
	 * Test the WorkerLogger with
	 */
	public function testLog()
	{
		$logger                    = \Mockery::mock(LoggerInterface::class);
		$workLogger                = new WorkerLogger($logger);
		$context                   = $testContext = ['test' => 'it'];
		$testContext['worker_id']  = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = '';
		self::assertUid($testContext['worker_id']);
		$logger
			->shouldReceive('log')
			->with('debug', 'a test', $testContext)
			->once();
		$workLogger->log('debug', 'a test', $context);
	}


	/**
	 * Test the WorkerLogger after setting a worker function
	 */
	public function testChangedId()
	{
		$logger                    = \Mockery::mock(LoggerInterface::class);
		$workLogger                = new WorkerLogger($logger);
		$context                   = $testContext = ['test' => 'it'];
		$testContext['worker_id']  = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = '';
		self::assertUid($testContext['worker_id']);
		$logger
			->shouldReceive('log')
			->with('debug', 'a test', $testContext)
			->once();
		$workLogger->log('debug', 'a test', $context);

		$workLogger->setFunctionName('testFunc');

		self::assertNotEquals($testContext['worker_id'], $workLogger->getWorkerId());

		$context                   = $testContext = ['test' => 'it'];
		$testContext['worker_id']  = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = 'testFunc';
		self::assertUid($testContext['worker_id']);
		$logger
			->shouldReceive('log')
			->with('debug', 'a test', $testContext)
			->once();
		$workLogger->log('debug', 'a test', $context);
	}
}
