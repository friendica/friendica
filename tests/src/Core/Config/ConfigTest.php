<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Test\src\Core\Config;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Repository\Config as ConfigModel;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Test\MockedTest;
use Mockery\MockInterface;
use Mockery;

abstract class ConfigTest extends MockedTest
{
	use ArraySubsetAsserts;

	/** @var ConfigModel|MockInterface */
	protected $configModel;

	/** @var Cache */
	protected $configCache;

	/** @var IManageConfigValues */
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

		self::assertNotEmpty($result);
		self::assertArrayHasKey($cat, $result);
		self::assertArraySubset($data, $result[$cat]);
	}


	protected function setUp(): void
	{
		parent::setUp();

		// Create the config model
		$this->configModel = Mockery::mock(ConfigModel::class);
		$this->configCache = new Cache();
	}

	/**
	 * @return IManageConfigValues
	 */
	abstract public function getInstance();

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
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		// assert config is loaded everytime
		self::assertConfig('config', $data['config']);
	}

	/**
	 * Test the configuration load() method
	 *
	 * @param array $data
	 * @param array $load
	 */
	public function testLoad(array $data, array $load)
	{
		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		foreach ($load as $loadedCats) {
			$this->testedConfig->load($loadedCats);
		}

		// Assert at least loaded cats are loaded
		foreach ($load as $loadedCats) {
			self::assertConfig($loadedCats, $data[$loadedCats]);
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
	public function testCacheLoadDouble(array $data1, array $data2, array $expect = [])
	{
		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		foreach ($data1 as $cat => $data) {
			$this->testedConfig->load($cat);
		}

		// Assert at least loaded cats are loaded
		foreach ($data1 as $cat => $data) {
			self::assertConfig($cat, $data);
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
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		self::assertEmpty($this->testedConfig->getCache()->getAll());
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
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		self::assertTrue($this->testedConfig->set('test', 'it', $data));

		self::assertEquals($data, $this->testedConfig->get('test', 'it'));
		self::assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));
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
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		self::assertTrue($this->testedConfig->set('test', 'it', $data));

		self::assertEquals($data, $this->testedConfig->get('test', 'it'));
		self::assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));
	}

	/**
	 * Test the configuration get() method with wrong value and no db
	 */
	public function testGetWrongWithoutDB()
	{
		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		// without refresh
		self::assertNull($this->testedConfig->get('test', 'it'));

		/// beware that the cache returns '!<unset>!' and not null for a non existing value
		self::assertNull($this->testedConfig->getCache()->get('test', 'it'));

		// with default value
		self::assertEquals('default', $this->testedConfig->get('test', 'it', 'default'));

		// with default value and refresh
		self::assertEquals('default', $this->testedConfig->get('test', 'it', 'default', true));
	}

	/**
	 * Test the configuration get() method with refresh
	 *
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$this->configCache->load(['test' => ['it' => 'now']], Cache::SOURCE_FILE);

		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		// without refresh
		self::assertEquals('now', $this->testedConfig->get('test', 'it'));
		self::assertEquals('now', $this->testedConfig->getCache()->get('test', 'it'));

		// with refresh
		self::assertEquals($data, $this->testedConfig->get('test', 'it', null, true));
		self::assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));

		// without refresh and wrong value and default
		self::assertEquals('default', $this->testedConfig->get('test', 'not', 'default'));
		self::assertNull($this->testedConfig->getCache()->get('test', 'not'));
	}

	/**
	 * Test the configuration delete() method without a model/db
	 *
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$this->configCache->load(['test' => ['it' => $data]], Cache::SOURCE_FILE);

		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		self::assertEquals($data, $this->testedConfig->get('test', 'it'));
		self::assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));

		self::assertTrue($this->testedConfig->delete('test', 'it'));
		self::assertNull($this->testedConfig->get('test', 'it'));
		self::assertNull($this->testedConfig->getCache()->get('test', 'it'));

		self::assertEmpty($this->testedConfig->getCache()->getAll());
	}

	/**
	 * Test the configuration delete() method with a model/db
	 */
	public function testDeleteWithDB()
	{
		$this->configCache->load(['test' => ['it' => 'now', 'quarter' => 'true']], Cache::SOURCE_FILE);

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
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		// directly set the value to the cache
		$this->testedConfig->getCache()->set('test', 'it', 'now');

		self::assertEquals('now', $this->testedConfig->get('test', 'it'));
		self::assertEquals('now', $this->testedConfig->getCache()->get('test', 'it'));

		// delete from cache only
		self::assertTrue($this->testedConfig->delete('test', 'it'));
		// delete from db only
		self::assertTrue($this->testedConfig->delete('test', 'second'));
		// no delete
		self::assertFalse($this->testedConfig->delete('test', 'third'));
		// delete both
		self::assertTrue($this->testedConfig->delete('test', 'quarter'));

		self::assertEmpty($this->testedConfig->getCache()->getAll());
	}

	/**
	 * Test the configuration get() and set() method where the db value has a higher prio than the config file
	 */
	public function testSetGetHighPrio()
	{
		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		$this->testedConfig->getCache()->set('config', 'test', 'prio', Cache::SOURCE_FILE);
		self::assertEquals('prio', $this->testedConfig->get('config', 'test'));

		// now you have to get the new variable entry because of the new set the get refresh succeed as well
		self::assertTrue($this->testedConfig->set('config', 'test', '123'));
		self::assertEquals('123', $this->testedConfig->get('config', 'test', '', true));
	}

	/**
	 * Test the configuration get() and set() method where the db value has a lower prio than the env
	 */
	public function testSetGetLowPrio()
	{
		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());
		self::assertEquals('it', $this->testedConfig->get('config', 'test'));

		$this->testedConfig->getCache()->set('config', 'test', 'prio', Cache::SOURCE_ENV);
		// now you have to get the env variable entry as output, even with a new set (which failed) and a get refresh
		self::assertFalse($this->testedConfig->set('config', 'test', '123'));
		self::assertEquals('prio', $this->testedConfig->get('config', 'test', '', true));
	}
}
