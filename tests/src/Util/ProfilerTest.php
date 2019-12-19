<?php

namespace Friendica\Test\src\Util;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\IConfiguration;
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

	protected function setUp()
	{
		parent::setUp();

		$this->logger = \Mockery::mock(LoggerInterface::class);
	}

	/**
	 * Test the Profiler setup
	 */
	public function testSetUp()
	{
		$configCache = \Mockery::mock(ConfigCache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();
		$profiler = new Profiler($configCache);
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
			'parser' => [
				'timestamp' => time(),
				'name' => 'parser',
				'functions' => ['test', 'it8'],
			],
			'marktime' => [
				'timestamp' => time(),
				'name' => 'parser',
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
		$configCache = \Mockery::mock(ConfigCache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($configCache);

		foreach ($functions as $function) {
			$profiler->saveTimestamp($timestamp, $name, $function);
		}

		$this->assertGreaterThanOrEqual(0, $profiler->get($name));
	}

	/**
	 * Test the Profiler reset
	 * @dataProvider dataPerformance
	 */
	public function testReset($timestamp, $name, array $functions)
	{
		$configCache = \Mockery::mock(ConfigCache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($configCache);

		$profiler->saveTimestamp($timestamp, $name);
		$profiler->reset();

		$this->assertEquals(0, $profiler->get($name));
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

		$configCache = \Mockery::mock(ConfigCache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($configCache);

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
				$this->assertRegExp('/' . $function . ': \d+/', $output);
			}
		}
	}

	/**
	 * Test different enable and disable states of the profiler
	 */
	public function testEnableDisable()
	{
		$configCache = \Mockery::mock(ConfigCache::class);
		$configCache->shouldReceive('get')
		            ->with('system', 'profiler')
		            ->andReturn(true)
		            ->once();
		$configCache->shouldReceive('get')
		            ->with('rendertime', 'callstack')
		            ->andReturn(false)
		            ->once();

		$profiler = new Profiler($configCache);

		$this->assertFalse($profiler->isRendertime());
		$this->assertEmpty($profiler->getRendertimeString());

		$profiler->saveTimestamp(time(), 'network', 'test1');

		$config = \Mockery::mock(IConfiguration::class);
		$config->shouldReceive('get')
		            ->with('system', 'profiler')
		            ->andReturn(false)
		            ->once();
		$config->shouldReceive('get')
		            ->with('rendertime', 'callstack')
		            ->andReturn(false)
		            ->once();

		$profiler->update($config);

		$this->assertFalse($profiler->isRendertime());
		$this->assertEmpty($profiler->getRendertimeString());

		$config->shouldReceive('get')
		       ->with('system', 'profiler')
		       ->andReturn(true)
		       ->once();
		$config->shouldReceive('get')
		       ->with('rendertime', 'callstack')
		       ->andReturn(true)
		       ->once();

		$profiler->update($config);

		$profiler->saveTimestamp(time(), 'database', 'test2');

		$this->assertTrue($profiler->isRendertime());
		$output = $profiler->getRendertimeString();
		$this->assertRegExp('/test1: \d+/', $output);
		$this->assertRegExp('/test2: \d+/', $output);
	}
}
