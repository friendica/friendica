<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Config;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Model\DatabaseConfig;
use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Test\DatabaseTestCase;
use Friendica\Test\Util\CreateDatabaseTrait;
use Friendica\Test\Util\VFSTrait;

class ConfigTest extends DatabaseTestCase
{
	use ArraySubsetAsserts;
	use VFSTrait;
	use CreateDatabaseTrait;

	/** @var Cache */
	protected $configCache;

	/** @var ConfigFileManager */
	protected $configFileManager;

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
		$this->setUpVfsDir();

		parent::setUp();

		$this->configCache       = new Cache();
		$this->configFileManager = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			$this->root->url() . '/config',
			$this->root->url() . '/static'
		);
	}

	/**
	 * @return IManageConfigValues
	 */
	public function getInstance()
	{
		$this->configFileManager->setupCache($this->configCache);
		return new DatabaseConfig($this->getDbInstance(), $this->configCache);
	}

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
			'other' => [
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
				'load' => [
					'system',
				],
			],
			'other' => [
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load' => [
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
				'load' => [
					'config',
				],
			],
			'all' => [
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load' => [
					'system',
					'config',
					'other'
				],
			],
		];
	}

	public function configToDbArray(array $config): array
	{
		$dbarray = [];

		foreach ($config as $category => $data) {
			foreach ($data as $key => $value) {
				$dbarray[] = [
					'cat' => $category,
					'k'   => $key,
					'v'   => $value,
				];
			}
		}

		return ['config' => $dbarray];
	}

	/**
	 * Test the configuration initialization
	 * @dataProvider dataConfigLoad
	 */
	public function testSetUp(array $data)
	{
		$this->loadDirectFixture($this->configToDbArray($data), $this->getDbInstance());

		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		// assert config is loaded everytime
		self::assertConfig('config', $data['config']);
	}

	/**
	 * Test the configuration reload() method
	 *
	 * @param array $data
	 * @param array $load
	 *
	 * @dataProvider dataConfigLoad
	 */
	public function testReload(array $data, array $load)
	{
		$this->loadDirectFixture($this->configToDbArray($data), $this->getDbInstance());

		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		$this->testedConfig->reload();

		// Assert at least loaded cats are loaded
		foreach ($load as $loadedCats) {
			self::assertConfig($loadedCats, $data[$loadedCats]);
		}
	}

	public function dataDoubleLoad()
	{
		return [
			'config' => [
				'data1' => [
					'config' => [
						'key1' => 'value1',
						'key2' => 'value2',
					],
				],
				'data2' => [
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
			'other' => [
				'data1' => [
					'config' => [
						'key12' => 'data4',
						'key45' => 7,
					],
					'other' => [
						'key1' => 'value1',
						'key2' => 'value2',
					],
				],
				'data2' => [
					'other' => [
						'key1' => 'overwritten!',
						'key3' => 'value3',
					],
					'config' => [
						'key45' => 45,
						'key52' => true,
					]
				],
				'expect' => [
					'other' => [
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
	 *
	 * @dataProvider dataDoubleLoad
	 */
	public function testCacheLoadDouble(array $data1, array $data2, array $expect = [])
	{
		$this->loadDirectFixture($this->configToDbArray($data1), $this->getDbInstance());

		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		// Assert at least loaded cats are loaded
		foreach ($data1 as $cat => $data) {
			self::assertConfig($cat, $data);
		}

		$this->loadDirectFixture($this->configToDbArray($data2), $this->getDbInstance());

		$this->testedConfig->reload();

		foreach ($data2 as $cat => $data) {
			self::assertConfig($cat, $data);
		}
	}

	/**
	 * Test the configuration load without result
	 */
	public function testLoadWrong()
	{
		$this->testedConfig = new ReadOnlyFileConfig(new Cache());
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		self::assertEmpty($this->testedConfig->getCache()->getAll());
	}

	/**
	 * Test the configuration get() and set() methods
	 *
	 * @dataProvider dataTests
	 */
	public function testSetGet($data)
	{
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

		/// beware that the cache returns '!<unset>!' and not null for a nonexistent value
		self::assertNull($this->testedConfig->getCache()->get('test', 'it'));

		// with default value
		self::assertEquals('default', $this->testedConfig->get('test', 'it', 'default'));

		// with default value and refresh
		self::assertEquals('default', $this->testedConfig->get('test', 'it', 'default', true));
	}

	/**
	 * Test the configuration delete() method without a model/db
	 *
	 * @dataProvider dataTests
	 */
	public function testDelete($data)
	{
		$this->configCache->load(['test' => ['it' => $data]], Cache::SOURCE_FILE);

		$this->testedConfig = new DatabaseConfig($this->getDbInstance(), $this->configCache);
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());

		self::assertEquals($data, $this->testedConfig->get('test', 'it'));
		self::assertEquals($data, $this->testedConfig->getCache()->get('test', 'it'));

		self::assertTrue($this->testedConfig->delete('test', 'it'));
		self::assertNull($this->testedConfig->get('test', 'it'));
		self::assertNull($this->testedConfig->getCache()->get('test', 'it'));
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
		$this->loadDirectFixture(['config' => [['cat' => 'config', 'k' => 'test', 'v' => 'it']]], $this->getDbInstance());

		$this->testedConfig = $this->getInstance();
		self::assertInstanceOf(Cache::class, $this->testedConfig->getCache());
		self::assertEquals('it', $this->testedConfig->get('config', 'test'));

		$this->testedConfig->getCache()->set('config', 'test', 'prio', Cache::SOURCE_ENV);
		// You can set a config value, but if there's a value with a higher priority (environment), this value will persist when retrieving
		self::assertTrue($this->testedConfig->set('config', 'test', '123'));
		self::assertEquals('prio', $this->testedConfig->get('config', 'test', '', true));
	}


	public function dataTestCat()
	{
		return [
			'test_with_hashmap' => [
				'data' => [
					'test_with_hashmap' => [
						'notifyall' => [
							'last_update' => 1671051565,
							'admin'       => true,
						],
						'blockbot' => [
							'last_update' => 1658952852,
							'admin'       => true,
						],
					],
					'config' => [
						'register_policy' => 2,
						'register_text'   => '',
						'sitename'        => 'Friendica Social Network23',
						'hostname'        => 'friendica.local',
						'private_addons'  => false,
					],
					'system' => [
						'dbclean_expire_conversation' => 90,
					],
				],
				'cat'       => 'test_with_hashmap',
				'assertion' => [
					'notifyall' => [
						'last_update' => 1671051565,
						'admin'       => true,
					],
					'blockbot' => [
						'last_update' => 1658952852,
						'admin'       => true,
					],
				],
			],
			'test_with_keys' => [
				'data' => [
					'test_with_keys' => [
						[
							'last_update' => 1671051565,
							'admin'       => true,
						],
						[
							'last_update' => 1658952852,
							'admin'       => true,
						],
					],
					'config' => [
						'register_policy' => 2,
						'register_text'   => '',
						'sitename'        => 'Friendica Social Network23',
						'hostname'        => 'friendica.local',
						'private_addons'  => false,
					],
					'system' => [
						'dbclean_expire_conversation' => 90,
					],
				],
				'cat'       => 'test_with_keys',
				'assertion' => [
					[
						'last_update' => 1671051565,
						'admin'       => true,
					],
					[
						'last_update' => 1658952852,
						'admin'       => true,
					],
				],
			],
			'test_with_inner_array' => [
				'data' => [
					'test_with_inner_array' => [
						'notifyall' => [
							'last_update' => 1671051565,
							'admin'       => [
								'yes' => true,
								'no'  => 1.5,
							],
						],
						'blogbot' => [
							'last_update' => 1658952852,
							'admin'       => true,
						],
					],
					'config' => [
						'register_policy' => 2,
						'register_text'   => '',
						'sitename'        => 'Friendica Social Network23',
						'hostname'        => 'friendica.local',
						'private_addons'  => false,
					],
					'system' => [
						'dbclean_expire_conversation' => 90,
					],
				],
				'cat'       => 'test_with_inner_array',
				'assertion' => [
					'notifyall' => [
						'last_update' => 1671051565,
						'admin'       => [
							'yes' => true,
							'no'  => 1.5,
						],
					],
					'blogbot' => [
						'last_update' => 1658952852,
						'admin'       => true,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataTestCat
	 */
	public function testGetCategory(array $data, string $category, array $assertion)
	{
		$this->configCache = new Cache($data);
		$config            = new ReadOnlyFileConfig($this->configCache);

		self::assertEquals($assertion, $config->get($category));
	}

	public function dataSerialized(): array
	{
		return [
			'default' => [
				'value'     => ['test' => ['array']],
				'assertion' => ['test' => ['array']],
			],
			'issue-12803' => [
				'value'     => 's:48:"s:40:"s:32:"https://punkrock-underground.com";";";',
				'assertion' => 'https://punkrock-underground.com',
			],
			'double-serialized-array' => [
				'value'     => 's:53:"a:1:{s:9:"testArray";a:1:{s:4:"with";s:7:"entries";}}";',
				'assertion' => ['testArray' => ['with' => 'entries']],
			],
		];
	}

	/**
	 * @dataProvider dataSerialized
	 */
	public function testSerializedValues($value, $assertion)
	{
		$config = $this->getInstance();

		$config->set('test', 'it', $value);
		self:self::assertEquals($assertion, $config->get('test', 'it'));
	}

	public function dataEnv(): array
	{
		$data = [
			'config' => [
				'admin_email' => 'value1',
				'timezone'    => 'value2',
				'language'    => 'value3',
				'sitename'    => 'value',
			],
			'system' => [
				'url'       => 'value1a',
				'debugging' => true,
				'logfile'   => 'value4',
				'loglevel'  => 'notice',
				'proflier'  => true,
			],
			'proxy' => [
				'trusted_proxies' => 'value5',
			],
		];

		return [
			'empty' => [
				'data'           => $data,
				'server'         => [],
				'assertDisabled' => [],
			],
			'mixed' => [
				'data'   => $data,
				'server' => [
					'FRIENDICA_ADMIN_MAIL' => 'test@friendica.local',
					'FRIENDICA_DEBUGGING'  => true,
				],
				'assertDisabled' => [
					'config' => [
						'admin_email' => true,
					],
					'system' => [
						'debugging' => true,
					],
				],
			],
		];
	}

	/**
	 * Tests if environment variables can change the permission to write a config key
	 *
	 * @dataProvider dataEnv
	 */
	public function testIsWritable(array $data, array $server, array $assertDisabled)
	{
		$this->setConfigFile('static' . DIRECTORY_SEPARATOR . 'env.config.php', true);
		$this->loadDirectFixture($this->configToDbArray($data), $this->getDbInstance());

		$configFileManager = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			$this->root->url() . '/config',
			$this->root->url() . '/static',
			$server
		);
		$configFileManager->setupCache($this->configCache);
		$config = new DatabaseConfig($this->getDbInstance(), $this->configCache);

		foreach ($data as $category => $keyvalues) {
			foreach ($keyvalues as $key => $value) {
				if (empty($assertDisabled[$category][$key])) {
					static::assertTrue($config->isWritable($category, $key), sprintf('%s.%s is not true', $category, $key));
				} else {
					static::assertFalse($config->isWritable($category, $key), sprintf('%s.%s is not false', $category, $key));
				}
			}
		}
	}
}
