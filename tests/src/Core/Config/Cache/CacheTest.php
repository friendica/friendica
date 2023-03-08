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

namespace Friendica\Test\src\Core\Config\Cache;

use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Test\MockedTest;
use ParagonIE\HiddenString\HiddenString;
use stdClass;

class CacheTest extends MockedTest
{
	public function dataTests()
	{
		return [
			'normal' => [
				'data' => [
					'system' => [
						'test' => 'it',
						'boolTrue' => true,
						'boolFalse' => false,
						'int' => 235,
						'dec' => 2.456,
						'array' => ['1', 2, '3', true, false],
					],
					'config' => [
						'a' => 'value',
					],
				]
			]
		];
	}

	private function assertConfigValues($data, Cache $configCache)
	{
		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				self::assertEquals($data[$cat][$key], $configCache->get($cat, $key));
			}
		}
	}

	/**
	 * Test the loadConfigArray() method without override
	 * @dataProvider dataTests
	 */
	public function testLoadConfigArray($data)
	{
		$configCache = new Cache();
		$configCache->load($data);

		self::assertConfigValues($data, $configCache);
	}

	/**
	 * Test the loadConfigArray() method with overrides
	 * @dataProvider dataTests
	 */
	public function testLoadConfigArrayOverride($data)
	{
		$override = [
			'system' => [
				'test' => 'not',
				'boolTrue' => false,
			]
		];

		$configCache = new Cache();
		$configCache->load($data, Cache::SOURCE_DATA);
		// doesn't override - Low Priority due Config file
		$configCache->load($override, Cache::SOURCE_FILE);

		self::assertConfigValues($data, $configCache);

		// override the value - High Prio due Server Env
		$configCache->load($override, Cache::SOURCE_ENV);

		self::assertEquals($override['system']['test'], $configCache->get('system', 'test'));
		self::assertEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));

		// Don't overwrite server ENV variables - even in load mode
		$configCache->load($data, Cache::SOURCE_DATA);

		self::assertEquals($override['system']['test'], $configCache->get('system', 'test'));
		self::assertEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));

		// Overwrite ENV variables with ENV variables
		$configCache->load($data, Cache::SOURCE_ENV);

		self::assertConfigValues($data, $configCache);
		self::assertNotEquals($override['system']['test'], $configCache->get('system', 'test'));
		self::assertNotEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));
	}

	/**
	 * Test the loadConfigArray() method with only a category
	 */
	public function testLoadConfigArrayWithOnlyCategory()
	{
		$configCache = new Cache();

		// empty dataset
		$configCache->load([]);
		self::assertEmpty($configCache->getAll());

		// wrong dataset
		$configCache->load(['system' => 'not_array']);
		self::assertEquals([], $configCache->getAll());

		// incomplete dataset (key is integer ID of the array)
		$configCache = new Cache();
		$configCache->load(['system' => ['value']]);
		self::assertEquals('value', $configCache->get('system', 0));
	}

	/**
	 * Test the getAll() method
	 * @dataProvider dataTests
	 */
	public function testGetAll($data)
	{
		$configCache = new Cache();
		$configCache->load($data);

		$all = $configCache->getAll();

		self::assertContains($data['system'], $all);
		self::assertContains($data['config'], $all);
	}

	/**
	 * Test the set() and get() method
	 * @dataProvider dataTests
	 */
	public function testSetGet($data)
	{
		$configCache = new Cache();

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->set($cat, $key, $value);
			}
		}

		self::assertConfigValues($data, $configCache);
	}

	/**
	 * Test the get() method without a value
	 */
	public function testGetEmpty()
	{
		$configCache = new Cache();

		self::assertNull($configCache->get('something', 'value'));
	}

	/**
	 * Test the get() method with a category
	 */
	public function testGetCat()
	{
		$configCache = new Cache([
			'system' => [
				'key1' => 'value1',
				'key2' => 'value2',
			],
			'config' => [
				'key3' => 'value3',
			],
		]);

		self::assertEquals([
			'key1' => 'value1',
			'key2' => 'value2',
		], $configCache->get('system'));

		// explicit null as key
		self::assertEquals([
			'key1' => 'value1',
			'key2' => 'value2',
		], $configCache->get('system', null));
	}

	/**
	 * Test the delete() method
	 * @dataProvider dataTests
	 */
	public function testDelete($data)
	{
		$configCache = new Cache($data);

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->delete($cat, $key);
			}
		}

		self::assertEmpty($configCache->getAll());
	}

	/**
	 * Test the keyDiff() method with result
	 * @dataProvider dataTests
	 */
	public function testKeyDiffWithResult($data)
	{
		$configCache = new Cache($data);

		$diffConfig = [
			'fakeCat' => [
				'fakeKey' => 'value',
			]
		];

		self::assertEquals($diffConfig, $configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the keyDiff() method without result
	 * @dataProvider dataTests
	 */
	public function testKeyDiffWithoutResult($data)
	{
		$configCache = new Cache($data);

		$diffConfig = $configCache->getAll();

		self::assertEmpty($configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the default hiding of passwords inside the cache
	 */
	public function testPasswordHide()
	{
		$configCache = new Cache([
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			],
		]);

		self::assertEquals('supersecure', $configCache->get('database', 'password'));
		self::assertNotEquals('supersecure', print_r($configCache->get('database', 'password'), true));
		self::assertEquals('notsecured', print_r($configCache->get('database', 'username'), true));
	}

	/**
	 * Test disabling the hiding of passwords inside the cache
	 */
	public function testPasswordShow()
	{
		$configCache = new Cache([
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			],
		], false);

		self::assertEquals('supersecure', $configCache->get('database', 'password'));
		self::assertEquals('supersecure', print_r($configCache->get('database', 'password'), true));
		self::assertEquals('notsecured', print_r($configCache->get('database', 'username'), true));
	}

	/**
	 * Test a empty password
	 */
	public function testEmptyPassword()
	{
		$configCache = new Cache([
			'database' => [
				'password' => '',
				'username' => '',
			]
		]);

		self::assertNotEmpty($configCache->get('database', 'password'));
		self::assertInstanceOf(HiddenString::class, $configCache->get('database', 'password'));
		self::assertEmpty($configCache->get('database', 'username'));
	}

	public function testWrongTypePassword()
	{
		$configCache = new Cache([
			'database' => [
				'password' => new stdClass(),
				'username' => '',
			]
		]);

		self::assertNotEmpty($configCache->get('database', 'password'));
		self::assertEmpty($configCache->get('database', 'username'));

		$configCache = new Cache([
			'database' => [
				'password' => 23,
				'username' => '',
			]
		]);

		self::assertEquals(23, $configCache->get('database', 'password'));
		self::assertEmpty($configCache->get('database', 'username'));
	}

	/**
	 * Test the set() method with overrides
	 * @dataProvider dataTests
	 */
	public function testSetOverrides($data)
	{

		$configCache = new Cache();
		$configCache->load($data, Cache::SOURCE_DATA);

		// test with wrong override
		self::assertFalse($configCache->set('system', 'test', '1234567', Cache::SOURCE_FILE));
		self::assertEquals($data['system']['test'], $configCache->get('system', 'test'));

		// test with override (equal)
		self::assertTrue($configCache->set('system', 'test', '8910', Cache::SOURCE_DATA));
		self::assertEquals('8910', $configCache->get('system', 'test'));

		// test with override (over)
		self::assertTrue($configCache->set('system', 'test', '111213', Cache::SOURCE_ENV));
		self::assertEquals('111213', $configCache->get('system', 'test'));
	}

	/**
	 * @dataProvider dataTests
	 *
	 * @return void
	 */
	public function testSetData($data)
	{
		$configCache = new Cache();
		$configCache->load($data, Cache::SOURCE_FILE);

		$configCache->set('system', 'test_2','with_data', Cache::SOURCE_DATA);

		$this->assertEquals(['system' => ['test_2' => 'with_data']], $configCache->getDataBySource(Cache::SOURCE_DATA));
		$this->assertEquals($data, $configCache->getDataBySource(Cache::SOURCE_FILE));
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testMerge($data)
	{
		$configCache = new Cache();
		$configCache->load($data, Cache::SOURCE_FILE);

		$configCache->set('system', 'test_2','with_data', Cache::SOURCE_DATA);
		$configCache->set('config', 'test_override','with_another_data', Cache::SOURCE_DATA);
		$configCache->set('old_category', 'test_45','given category', Cache::SOURCE_DATA);

		$newCache = new Cache();
		$newCache->set('config', 'test_override','override it again', Cache::SOURCE_DATA);
		$newCache->set('system', 'test_3','new value', Cache::SOURCE_DATA);
		$newCache->set('new_category', 'test_23','added category', Cache::SOURCE_DATA);

		$mergedCache = $configCache->merge($newCache);

		self::assertEquals('with_data', $mergedCache->get('system', 'test_2'));
		self::assertEquals('override it again', $mergedCache->get('config', 'test_override'));
		self::assertEquals('new value', $mergedCache->get('system', 'test_3'));
		self::assertEquals('given category', $mergedCache->get('old_category', 'test_45'));
		self::assertEquals('added category', $mergedCache->get('new_category', 'test_23'));
	}

	public function dataTestCat()
	{
		return [
			'test_with_hashmap'     => [
				'data'      => [
					'test_with_hashmap' => [
						'notifyall' => [
							'last_update' => 1671051565,
							'admin'       => true,
						],
						'blockbot'  => [
							'last_update' => 1658952852,
							'admin'       => true,
						],
					],
					'config'            => [
						'register_policy' => 2,
						'register_text'   => '',
						'sitename'        => 'Friendica Social Network23',
						'hostname'        => 'friendica.local',
						'private_addons'  => false,
					],
					'system'            => [
						'dbclean_expire_conversation' => 90,
					],
				],
				'cat'       => 'test_with_hashmap',
				'assertion' => [
					'notifyall' => [
						'last_update' => 1671051565,
						'admin'       => true,
					],
					'blockbot'  => [
						'last_update' => 1658952852,
						'admin'       => true,
					],
				],
			],
			'test_with_keys'        => [
				'data'      => [
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
					'config'            => [
						'register_policy' => 2,
						'register_text'   => '',
						'sitename'        => 'Friendica Social Network23',
						'hostname'        => 'friendica.local',
						'private_addons'  => false,
					],
					'system'            => [
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
				'data'      => [
					'test_with_inner_array' => [
						'notifyall' => [
							'last_update' => 1671051565,
							'admin'       => [
								'yes' => true,
								'no'  => 1.5,
							],
						],
						'blogbot'   => [
							'last_update' => 1658952852,
							'admin'       => true,
						],
					],
					'config'                => [
						'register_policy' => 2,
						'register_text'   => '',
						'sitename'        => 'Friendica Social Network23',
						'hostname'        => 'friendica.local',
						'private_addons'  => false,
					],
					'system'                => [
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
					'blogbot'   => [
						'last_update' => 1658952852,
						'admin'       => true,
					],
				],
			],
			/** @see https://github.com/friendica/friendica/issues/12486#issuecomment-1374609349 */
			'test_with_null' => [
				'data'      => [
					'test_with_null' => null,
					'config'                => [
						'register_policy' => 2,
						'register_text'   => '',
						'sitename'        => 'Friendica Social Network23',
						'hostname'        => 'friendica.local',
						'private_addons'  => false,
					],
					'system'                => [
						'dbclean_expire_conversation' => 90,
					],
				],
				'cat'       => 'test_with_null',
				'assertion' => null,
			],
		];
	}

	/**
	 * Tests that the Cache can return a whole category at once
	 *
	 * @dataProvider dataTestCat
	 */
	public function testGetCategory($data, string $category, $assertion)
	{
		$cache = new Cache($data);

		self::assertEquals($assertion, $cache->get($category));
	}

	/**
	 * Test that the cache can get merged with different categories
	 *
	 * @dataProvider dataTestCat
	 */
	public function testCatMerge($data, string $category)
	{
		$cache = new Cache($data);

		$newCache = $cache->merge(new Cache([
			$category => [
				'new_key' => 'new_value',
			],
		]));

		self::assertEquals('new_value', $newCache->get($category, 'new_key'));
	}

	/**
	 * Test that keys are removed after a deletion
	 *
	 * @dataProvider dataTests
	 *
	 */
	public function testDeleteRemovesKey($data)
	{
		$cache = new Cache();
		$cache->load($data, Cache::SOURCE_FILE);

		$cache->set('system', 'test', 'overwrite!', Cache::SOURCE_DATA);
		self::assertEquals('overwrite!', $cache->get('system', 'test'));

		// array should now be removed
		$cache->delete('system', 'test');
		self::assertArrayNotHasKey('test', $cache->getAll()['system']);

		self::assertArrayHasKey('config', $cache->getAll());
		self::assertArrayHasKey('a', $cache->getAll()['config']);

		// category should now be removed
		$cache->delete('config', 'a');
		self::assertArrayNotHasKey('config', $cache->getAll());
	}

	/**
	 * Test that deleted keys are working with merge
	 *
	 * @dataProvider dataTests
	 */
	public function testDeleteAndMergeWithDefault($data)
	{
		$cache = new Cache();
		$cache->load($data, Cache::SOURCE_FILE);

		$cache2 = new Cache();
		$cache2->set('system', 'test', 'overrride');
		$cache2->delete('system', 'test');

		self::assertEquals('it', $cache->get('system', 'test'));
		self::assertNull($cache2->get('system', 'test'));

		$mergedCache = $cache->merge($cache2);
		self::assertNull($mergedCache->get('system', 'test'));
	}
}
