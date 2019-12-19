<?php

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Model\Config\Config as ConfigModel;
use Friendica\Test\MockedTest;
use Mockery\MockInterface;
use Mockery;

abstract class ConfigurationTest extends MockedTest
{
	/** @var ConfigModel|MockInterface */
	protected $configModel;

	/** @var ConfigCache */
	protected $configCache;

	/** @var IConfiguration */
	protected $testedConfig;

	/**
	 * Assert a config tree
	 *
	 * @param string $cat  The category to assert
	 * @param array  $data The result data array
	 */
	protected function assertConfig(string $cat, array $data)
	{
		$result = $this->testedConfig->getCache()->getAll();

		$this->assertNotEmpty($result);
		$this->assertArrayHasKey($cat, $result);
		$this->assertArraySubset($data, $result[$cat]);
	}


	protected function setUp()
	{
		parent::setUp();

		// Create the config model
		$this->configModel = Mockery::mock(ConfigModel::class);
		$this->configCache = new ConfigCache();
	}

	/**
	 * @return IConfiguration
	 */
	public abstract function getInstance();

	public function dataTests()
	{
		return [
			'string'       => ['data' => 'it'],
			'boolTrue'     => ['data' => true],
			'boolFalse'    => ['data' => false],
			'integer'      => ['data' => 235],
			'decimal'      => ['data' => 2.456],
			'array'        => ['data' => ['1', 2, '3', true, false]],
			'boolIntTrue'  => ['data' => 1],
			'boolIntFalse' => ['Data' => 0],
		];
	}

	public function dataConfigLoad()
	{
		$data = [
			'system' => [
				'key1' => 'value1',
				'key2' => 'value2',
				'key3' => 'value3',
			],
			'config' => [
				'key1' => 'value1a',
				'key4' => 'value4',
			],
			'other'  => [
				'key5' => 'value5',
				'key6' => 'value6',
			],
		];

		return [
			'system' => [
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'system',
				],
			],
			'other'  => [
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'other',
				],
			],
			'config' => [
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'config',
				],
			],
			'all'    => [
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'system',
					'config',
					'other'
				],
			],
		];
	}

	/**
	 * Test the configuration initialization
	 */
	public function testSetUp(array $data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->once();

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		// assert config is loaded everytime
		$this->assertConfig('config', $data['config']);
	}

	/**
	 * Test the configuration load() method
	 */
	public function testLoad(array $data, array $possibleCats, array $load)
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		foreach ($load as $loadedCats) {
			$this->testedConfig->load($loadedCats);
		}

		// Assert at least loaded cats are loaded
		foreach ($load as $loadedCats) {
			$this->assertConfig($loadedCats, $data[$loadedCats]);
		}
	}

	public function dataDoubleLoad()
	{
		return [
			'config' => [
				'data1'  => [
					'config' => [
						'key1' => 'value1',
						'key2' => 'value2',
					],
				],
				'data2'  => [
					'config' => [
						'key1' => 'overwritten!',
						'key3' => 'value3',
					],
				],
				'expect' => [
					'config' => [
						// load should overwrite values everytime!
						'key1' => 'overwritten!',
						'key2' => 'value2',
						'key3' => 'value3',
					],
				],
			],
			'other'  => [
				'data1'  => [
					'config' => [
						'key12' => 'data4',
						'key45' => 7,
					],
					'other'  => [
						'key1' => 'value1',
						'key2' => 'value2',
					],
				],
				'data2'  => [
					'other'  => [
						'key1' => 'overwritten!',
						'key3' => 'value3',
					],
					'config' => [
						'key45' => 45,
						'key52' => true,
					]
				],
				'expect' => [
					'other'  => [
						// load should overwrite values everytime!
						'key1' => 'overwritten!',
						'key2' => 'value2',
						'key3' => 'value3',
					],
					'config' => [
						'key12' => 'data4',
						'key45' => 45,
						'key52' => true,
					],
				],
			],
		];
	}

	/**
	 * Test the configuration load() method with overwrite
	 */
	public function testCacheLoadDouble(array $data1, array $data2, array $expect)
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		foreach ($data1 as $cat => $data) {
			$this->testedConfig->load($cat);
		}

		// Assert at least loaded cats are loaded
		foreach ($data1 as $cat => $data) {
			$this->assertConfig($cat, $data);
		}

		foreach ($data2 as $cat => $data) {
			$this->testedConfig->load($cat);
		}
	}

	/**
	 * Test the configuration load without result
	 */
	public function testLoadWrong()
	{
		$this->configModel->shouldReceive('isConnected')->andReturn(true)->once();
		$this->configModel->shouldReceive('load')->withAnyArgs()->andReturn([])->once();

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		$this->assertEmpty($this->testedConfig->getCache()->getAll());
	}

	/**
	 * Test the configuration get() and set() methods without adapter
	 *
	 * @dataProvider dataTests
	 */
	public function testSetGetWithoutDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(3);

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		$this->assertTrue($this->testedConfig->set('test', 'it', $data));

		$this->assertEquals($data, $this->testedConfig->get('test', 'it'));
		$this->assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));
	}

	/**
	 * Test the configuration get() and set() methods with a model/db
	 *
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB($data)
	{
		$this->configModel->shouldReceive('set')->with('test', 'it', $data)->andReturn(true)->once();

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		$this->assertTrue($this->testedConfig->set('test', 'it', $data));

		$this->assertEquals($data, $this->testedConfig->get('test', 'it'));
		$this->assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));
	}

	/**
	 * Test the configuration get() method with wrong value and no db
	 */
	public function testGetWrongWithoutDB()
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		// without refresh
		$this->assertNull($this->testedConfig->get('test', 'it'));

		/// beware that the cache returns '!<unset>!' and not null for a non existing value
		$this->assertNull($this->testedConfig->getCache()->get('test', 'it'));

		// with default value
		$this->assertEquals('default', $this->testedConfig->get('test', 'it', 'default'));

		// with default value and refresh
		$this->assertEquals('default', $this->testedConfig->get('test', 'it', 'default', true));
	}

	/**
	 * Test the configuration get() method with refresh
	 *
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$this->configCache->load(['test' => ['it' => 'now']]);

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		// without refresh
		$this->assertEquals('now', $this->testedConfig->get('test', 'it'));
		$this->assertEquals('now', $this->testedConfig->getCache()->get('test', 'it'));

		// with refresh
		$this->assertEquals($data, $this->testedConfig->get('test', 'it', null, true));
		$this->assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));

		// without refresh and wrong value and default
		$this->assertEquals('default', $this->testedConfig->get('test', 'not', 'default'));
		$this->assertNull($this->testedConfig->getCache()->get('test', 'not'));
	}

	/**
	 * Test the configuration delete() method without a model/db
	 *
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$this->configCache->load(['test' => ['it' => $data]]);

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		$this->assertEquals($data, $this->testedConfig->get('test', 'it'));
		$this->assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));

		$this->assertTrue($this->testedConfig->delete('test', 'it'));
		$this->assertNull($this->testedConfig->get('test', 'it'));
		$this->assertNull($this->testedConfig->getCache()->get('test', 'it'));

		$this->assertEmpty($this->testedConfig->getCache()->getAll());
	}

	/**
	 * Test the configuration delete() method with a model/db
	 */
	public function testDeleteWithDB()
	{
		$this->configCache->load(['test' => ['it' => 'now', 'quarter' => 'true']]);

		$this->configModel->shouldReceive('delete')
		                  ->with('test', 'it')
		                  ->andReturn(false)
		                  ->once();
		$this->configModel->shouldReceive('delete')
		                  ->with('test', 'second')
		                  ->andReturn(true)
		                  ->once();
		$this->configModel->shouldReceive('delete')
		                  ->with('test', 'third')
		                  ->andReturn(false)
		                  ->once();
		$this->configModel->shouldReceive('delete')
		                  ->with('test', 'quarter')
		                  ->andReturn(true)
		                  ->once();

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(ConfigCache::class, $this->testedConfig->getCache());

		// directly set the value to the cache
		$this->testedConfig->getCache()->set('test', 'it', 'now');

		$this->assertEquals('now', $this->testedConfig->get('test', 'it'));
		$this->assertEquals('now', $this->testedConfig->getCache()->get('test', 'it'));

		// delete from cache only
		$this->assertTrue($this->testedConfig->delete('test', 'it'));
		// delete from db only
		$this->assertTrue($this->testedConfig->delete('test', 'second'));
		// no delete
		$this->assertFalse($this->testedConfig->delete('test', 'third'));
		// delete both
		$this->assertTrue($this->testedConfig->delete('test', 'quarter'));

		$this->assertEmpty($this->testedConfig->getCache()->getAll());
	}
}
