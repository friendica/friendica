<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Logger\Type;

use Friendica\Core\Logger\Type\WorkerLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkerLoggerTest extends TestCase
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
	public function testLogMethod($func, $msg, $context = [])
	{
		$logger = $this->createMock(LoggerInterface::class);

		$workLogger = new WorkerLogger($logger);

		$testContext               = $context;
		$testContext['worker_id']  = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = '';
		self::assertUid($testContext['worker_id']);


		$logger->expects(self::once())->method($func)->with($msg, $testContext);

		$workLogger->$func($msg, $context);
	}

	/**
	 * Test the WorkerLogger with
	 */
	public function testLog()
	{
		$logger = $this->createMock(LoggerInterface::class);

		$workLogger = new WorkerLogger($logger);

		$context = $testContext = ['test' => 'it'];

		$testContext['worker_id']  = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = '';
		self::assertUid($testContext['worker_id']);

		$logger->expects(self::once())->method('log')->with('debug', 'a test', $testContext);

		$workLogger->log('debug', 'a test', $context);
	}

	/**
	 * Test the WorkerLogger after setting a worker function
	 */
	public function testChangedId()
	{
		$logger = $this->createMock(LoggerInterface::class);

		$workLogger = new WorkerLogger($logger);

		$context = $testContext1 = ['test' => 'it'];

		$testContext1['worker_id']  = $workLogger->getWorkerId();
		$testContext1['worker_cmd'] = '';
		self::assertUid($testContext1['worker_id']);

		$context2 = $testContext2 = ['test' => 'it'];

		$testContext2['worker_id']  = $workLogger->getWorkerId();
		$testContext2['worker_cmd'] = 'testFunc';
		self::assertUid($testContext2['worker_id']);

		$logger->expects(self::exactly(2))->method('log')->willReturnMap([
			['debug', 'a test', $testContext1],
			['debug', 'a test', $testContext2],
		]);

		$workLogger->log('debug', 'a test', $context);

		$workLogger->setFunctionName('testFunc');

		self::assertNotEquals($testContext1['worker_id'], $workLogger->getWorkerId());

		$workLogger->log('debug', 'a test', $context2);
	}

	public function testReplaceDefaultContextReturnsOldDefaultContext()
	{
		$logger = $this->createStub(LoggerInterface::class);

		$workLogger = new WorkerLogger($logger);

		$newContext = ['worker_id' => 'new_id', 'worker_cmd' => 'new_cmd'];

		$this->assertSame(
			['worker_id', 'worker_cmd'],
			array_keys($workLogger->replaceDefaultContext($newContext))
		);

		$this->assertSame(
			$newContext,
			$workLogger->replaceDefaultContext([])
		);
	}
}
