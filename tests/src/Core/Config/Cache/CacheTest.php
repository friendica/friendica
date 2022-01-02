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

namespace Friendica\Test\src\Core\Config\Cache;

use Friendica\Core\Config\Cache;
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

	private function assertConfigValues($data, \Friendica\Core\Config\ValueObject\Cache $configCache)
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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache();
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

		$configCache = new \Friendica\Core\Config\ValueObject\Cache();
		$configCache->load($data, \Friendica\Core\Config\ValueObject\Cache::SOURCE_DB);
		// doesn't override - Low Priority due Config file
		$configCache->load($override, \Friendica\Core\Config\ValueObject\Cache::SOURCE_FILE);

		self::assertConfigValues($data, $configCache);

		// override the value - High Prio due Server Env
		$configCache->load($override, \Friendica\Core\Config\ValueObject\Cache::SOURCE_ENV);

		self::assertEquals($override['system']['test'], $configCache->get('system', 'test'));
		self::assertEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));

		// Don't overwrite server ENV variables - even in load mode
		$configCache->load($data, \Friendica\Core\Config\ValueObject\Cache::SOURCE_DB);

		self::assertEquals($override['system']['test'], $configCache->get('system', 'test'));
		self::assertEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));

		// Overwrite ENV variables with ENV variables
		$configCache->load($data, \Friendica\Core\Config\ValueObject\Cache::SOURCE_ENV);

		self::assertConfigValues($data, $configCache);
		self::assertNotEquals($override['system']['test'], $configCache->get('system', 'test'));
		self::assertNotEquals($override['system']['boolTrue'], $configCache->get('system', 'boolTrue'));
	}

	/**
	 * Test the loadConfigArray() method with wrong/empty datasets
	 */
	public function testLoadConfigArrayWrong()
	{
		$configCache = new \Friendica\Core\Config\ValueObject\Cache();

		// empty dataset
		$configCache->load([]);
		self::assertEmpty($configCache->getAll());

		// wrong dataset
		$configCache->load(['system' => 'not_array']);
		self::assertEmpty($configCache->getAll());

		// incomplete dataset (key is integer ID of the array)
		$configCache->load(['system' => ['value']]);
		self::assertEquals('value', $configCache->get('system', 0));
	}

	/**
	 * Test the getAll() method
	 * @dataProvider dataTests
	 */
	public function testGetAll($data)
	{
		$configCache = new \Friendica\Core\Config\ValueObject\Cache();
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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache();

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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache();

		self::assertNull($configCache->get('something', 'value'));
	}

	/**
	 * Test the get() method with a category
	 */
	public function testGetCat()
	{
		$configCache = new \Friendica\Core\Config\ValueObject\Cache([
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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache($data);

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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache($data);

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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache($data);

		$diffConfig = $configCache->getAll();

		self::assertEmpty($configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the default hiding of passwords inside the cache
	 */
	public function testPasswordHide()
	{
		$configCache = new \Friendica\Core\Config\ValueObject\Cache([
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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache([
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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache([
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
		$configCache = new \Friendica\Core\Config\ValueObject\Cache([
			'database' => [
				'password' => new stdClass(),
				'username' => '',
			]
		]);

		self::assertNotEmpty($configCache->get('database', 'password'));
		self::assertEmpty($configCache->get('database', 'username'));

		$configCache = new \Friendica\Core\Config\ValueObject\Cache([
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

		$configCache = new \Friendica\Core\Config\ValueObject\Cache();
		$configCache->load($data, \Friendica\Core\Config\ValueObject\Cache::SOURCE_DB);

		// test with wrong override
		self::assertFalse($configCache->set('system', 'test', '1234567', \Friendica\Core\Config\ValueObject\Cache::SOURCE_FILE));
		self::assertEquals($data['system']['test'], $configCache->get('system', 'test'));

		// test with override (equal)
		self::assertTrue($configCache->set('system', 'test', '8910', \Friendica\Core\Config\ValueObject\Cache::SOURCE_DB));
		self::assertEquals('8910', $configCache->get('system', 'test'));

		// test with override (over)
		self::assertTrue($configCache->set('system', 'test', '111213', \Friendica\Core\Config\ValueObject\Cache::SOURCE_ENV));
		self::assertEquals('111213', $configCache->get('system', 'test'));
	}
}
