<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Type\WorkerLogger;
use Friendica\Test\MockedTest;
use Psr\Log\LoggerInterface;

class WorkerLoggerTest extends MockedTest
{
	private function assertUid($uid, $length = 7)
	{
		self::assertRegExp('/^[a-zA-Z0-9]{' . $length . '}+$/', $uid);
	}

	/**
	 * Test the a id with length zero
	 *
	 */
	public function testGetWorkerIdZero()
	{
		$this->expectException(\Error::class);
		
		$logger = \Mockery::mock(LoggerInterface::class);
		new WorkerLogger($logger, 'test', 0);
	}

	/**
	 * Test the generated Uid
	 */
	public function testGetWorkerId()
	{
		$logger = \Mockery::mock(LoggerInterface::class);
		for ($i = 1; $i < 14; $i++) {
			$workLogger = new WorkerLogger($logger, 'test', $i);
			$uid = $workLogger->getWorkerId();
			self::assertUid($uid, $i);
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
		$logger = \Mockery::mock(LoggerInterface::class);
		$workLogger = new WorkerLogger($logger, 'test');
		$testContext = $context;
		$testContext['worker_id'] = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = 'test';
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
		$logger = \Mockery::mock(LoggerInterface::class);
		$workLogger = new WorkerLogger($logger, 'test');
		$context = $testContext = ['test' => 'it'];
		$testContext['worker_id'] = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = 'test';
		self::assertUid($testContext['worker_id']);
		$logger
			->shouldReceive('log')
			->with('debug', 'a test', $testContext)
			->once();
		$workLogger->log('debug', 'a test', $context);
	}
}
