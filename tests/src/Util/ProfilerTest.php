<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MockedTestCase;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ProfilerTest extends MockedTestCase
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
	public function testSetUp(): void
	{
		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')
					->withAnyArgs()
					->andReturn(true)
					->twice();
		$profiler = new Profiler($config);

		self::assertInstanceOf(Profiler::class, $profiler); // @phpstan-ignore staticMethod.alreadyNarrowedType
	}

	/**
	 * A dataset for different profiling settings
	 * @return array
	 */
	public static function dataPerformance()
	{
		return [
			'database' => [
				'timestamp' => time(),
				'name'      => 'database',
				'functions' => ['test', 'it'],
			],
			'database_write' => [
				'timestamp' => time(),
				'name'      => 'database_write',
				'functions' => ['test', 'it2'],
			],
			'cache' => [
				'timestamp' => time(),
				'name'      => 'cache',
				'functions' => ['test', 'it3'],
			],
			'cache_write' => [
				'timestamp' => time(),
				'name'      => 'cache_write',
				'functions' => ['test', 'it4'],
			],
			'network' => [
				'timestamp' => time(),
				'name'      => 'network',
				'functions' => ['test', 'it5'],
			],
			'file' => [
				'timestamp' => time(),
				'name'      => 'file',
				'functions' => [],
			],
			'rendering' => [
				'timestamp' => time(),
				'name'      => 'rendering',
				'functions' => ['test', 'it7'],
			],
			'session' => [
				'timestamp' => time(),
				'name'      => 'session',
				'functions' => ['test', 'it8'],
			],
			'marktime' => [
				'timestamp' => time(),
				'name'      => 'session',
				'functions' => ['test'],
			],
			// This one isn't set during reset
			'unknown' => [
				'timestamp' => time(),
				'name'      => 'unknown',
				'functions' => ['test'],
			],
		];
	}

	/**
	 * Test the Profiler savetimestamp
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPerformance')]
	public function testSaveTimestamp($timestamp, $name, array $functions): void
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
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPerformance')]
	public function testReset($timestamp, $name, array $functions): void
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

	public static function dataBig()
	{
		return [
			'big' => [
				'data' => [
					'database' => [
						'timestamp' => time(),
						'name'      => 'database',
						'functions' => ['test', 'it'],
					],
					'database_write' => [
						'timestamp' => time(),
						'name'      => 'database_write',
						'functions' => ['test', 'it2'],
					],
					'cache' => [
						'timestamp' => time(),
						'name'      => 'cache',
						'functions' => ['test', 'it3'],
					],
					'cache_write' => [
						'timestamp' => time(),
						'name'      => 'cache_write',
						'functions' => ['test', 'it4'],
					],
					'network' => [
						'timestamp' => time(),
						'name'      => 'network',
						'functions' => ['test', 'it5'],
					],
				],
			],
		];
	}

	/**
	 * Test the output of the Profiler
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataBig')]
	public function testSaveLog($data): void
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
