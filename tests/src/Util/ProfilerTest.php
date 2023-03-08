<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Test\src\Util;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MockedTest;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ProfilerTest extends MockedTest
{
	/**
	 * @var LoggerInterface|MockInterface
	 */
	private $logger;

	protected function setUp(): void
	{
		parent::setUp();

		$this->logger = \Mockery::mock(LoggerInterface::class);
	}

	/**
	 * Test the Profiler setup
	 */
	public function testSetUp()
	{
		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();
		$profiler = new Profiler($config);

		self::assertInstanceOf(Profiler::class, $profiler);
	}

	/**
	 * A dataset for different profiling settings
	 * @return array
	 */
	public function dataPerformance()
	{
		return [
			'database' => [
				'timestamp' => time(),
				'name' => 'database',
				'functions' => ['test', 'it'],
			],
			'database_write' => [
				'timestamp' => time(),
				'name' => 'database_write',
				'functions' => ['test', 'it2'],
			],
			'cache' => [
				'timestamp' => time(),
				'name' => 'cache',
				'functions' => ['test', 'it3'],
			],
			'cache_write' => [
				'timestamp' => time(),
				'name' => 'cache_write',
				'functions' => ['test', 'it4'],
			],
			'network' => [
				'timestamp' => time(),
				'name' => 'network',
				'functions' => ['test', 'it5'],
			],
			'file' => [
				'timestamp' => time(),
				'name' => 'file',
				'functions' => [],
			],
			'rendering' => [
				'timestamp' => time(),
				'name' => 'rendering',
				'functions' => ['test', 'it7'],
			],
			'session' => [
				'timestamp' => time(),
				'name' => 'session',
				'functions' => ['test', 'it8'],
			],
			'marktime' => [
				'timestamp' => time(),
				'name' => 'session',
				'functions' => ['test'],
			],
			// This one isn't set during reset
			'unknown' => [
				'timestamp' => time(),
				'name' => 'unknown',
				'functions' => ['test'],
			],
		];
	}

	/**
	 * Test the Profiler savetimestamp
	 * @dataProvider dataPerformance
	 */
	public function testSaveTimestamp($timestamp, $name, array $functions)
	{
		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($config);

		foreach ($functions as $function) {
			$profiler->saveTimestamp($timestamp, $name, $function);
		}

		self::assertGreaterThanOrEqual(0, $profiler->get($name));
	}

	/**
	 * Test the Profiler reset
	 * @dataProvider dataPerformance
	 */
	public function testReset($timestamp, $name)
	{
		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($config);

		$profiler->saveTimestamp($timestamp, $name);
		$profiler->reset();

		self::assertEquals(0, $profiler->get($name));
	}

	public function dataBig()
	{
		return [
			'big' => [
				'data' => [
					'database' => [
						'timestamp' => time(),
						'name' => 'database',
						'functions' => ['test', 'it'],
					],
					'database_write' => [
						'timestamp' => time(),
						'name' => 'database_write',
						'functions' => ['test', 'it2'],
					],
					'cache' => [
						'timestamp' => time(),
						'name' => 'cache',
						'functions' => ['test', 'it3'],
					],
					'cache_write' => [
						'timestamp' => time(),
						'name' => 'cache_write',
						'functions' => ['test', 'it4'],
					],
					'network' => [
						'timestamp' => time(),
						'name' => 'network',
						'functions' => ['test', 'it5'],
					],
				]
			]
		];
	}

	/**
	 * Test the output of the Profiler
	 * @dataProvider dataBig
	 */
	public function testSaveLog($data)
	{
		$this->logger
			->shouldReceive('info')
			->with('test', \Mockery::any())
			->once();
		$this->logger
			->shouldReceive('info')
			->once();

		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($config);

		foreach ($data as $perf => $items) {
			foreach ($items['functions'] as $function) {
				$profiler->saveTimestamp($items['timestamp'], $items['name'], $function);
			}
		}

		$profiler->saveLog($this->logger, 'test');

		$output = $profiler->getRendertimeString();

		foreach ($data as $perf => $items) {
			foreach ($items['functions'] as $function) {
				// assert that the output contains the functions
				self::assertMatchesRegularExpression('/' . $function . ': \d+/', $output);
			}
		}
	}
}
