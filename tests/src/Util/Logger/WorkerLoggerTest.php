<?php

namespace src\Util\Logger;

use Friendica\Test\MockedTest;
use Friendica\Util\Logger\WorkerLogger;

class WorkerLoggerTest extends MockedTest
{
	/**
	 * Test the generated Uid
	 */
	public function testGetWorkerId()
	{
		$logger = \Mockery::mock('Psr\Log\LoggerInterface');

		for ($i = 0; $i < 10; $i++) {
			$workLogger = new WorkerLogger($logger, 'test');

			$uid = $workLogger->getWorkerId();
			$this->assertRegExp('/^[a-zA-Z0-9]{7}+$/', $uid);
		}
	}

	public function dataTest()
	{
		return [
			'info' => [
				'func' => 'info',
				'msg' => 'the alert',
				'context' => [],
			],
			'alert' => [
				'func' => 'alert',
				'msg' => 'another alert',
				'context' => ['test' => 'it'],
			],
			'critical' => [
				'func' => 'critical',
				'msg' => 'Critical msg used',
				'context' => ['test' => 'it', 'more' => 0.24545],
			],
			'error' => [
				'func' => 'error',
				'msg' => 21345623,
				'context' => ['test' => 'it', 'yet' => true],
			],
			'warning' => [
				'func' => 'warning',
				'msg' => 'another alert' . 123523 . 324.54534 . 'test',
				'context' => ['test' => 'it', 2 => 'nope'],
			],
			'notice' => [
				'func' => 'notice',
				'msg' => 'Notice' . ' alert' . true . 'with' . '\'strange\'' . 1.24. 'behavior',
				'context' => ['test' => 'it'],
			],
			'debug' => [
				'func' => 'debug',
				'msg' => 'at last a debug',
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
		$logger = \Mockery::mock('Psr\Log\LoggerInterface');
		$workLogger = new WorkerLogger($logger, 'test');

		$testContext = $context;

		$testContext['worker_id'] = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = 'test';
		$this->assertRegExp('/^[a-zA-Z0-9]{7}+$/', $testContext['worker_id']);

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
		$logger = \Mockery::mock('Psr\Log\LoggerInterface');
		$workLogger = new WorkerLogger($logger, 'test');

		$context = $testContext = ['test' => 'it'];
		$testContext['worker_id'] = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = 'test';
		$this->assertRegExp('/^[a-zA-Z0-9]{7}+$/', $testContext['worker_id']);

		$logger
			->shouldReceive('log')
			->with('debug', 'a test', $testContext)
			->once();

		$workLogger->log('debug', 'a test', $context);
	}
}
