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

namespace Friendica\Test\src\Core\PConfig\Cache;

use Friendica\Core\PConfig\Cache;
use Friendica\Test\MockedTest;

class CacheTest extends MockedTest
{
	public function dataTests()
	{
		return [
			'normal' => [
				'data' => [
					'system' => [
						'test'      => 'it',
						'boolTrue'  => true,
						'boolFalse' => false,
						'int'       => 235,
						'dec'       => 2.456,
						'array'     => ['1', 2, '3', true, false],
					],
					'config' => [
						'a' => 'value',
					],
				]
			]
		];
	}

	private function assertConfigValues($data, Cache\Cache $configCache, $uid)
	{
		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				self::assertEquals($data[$cat][$key], $configCache->get($uid, $cat, $key));
			}
		}
	}

	/**
	 * Test the setP() and getP() methods
	 *
	 * @dataProvider dataTests
	 */
	public function testSetGet($data)
	{
		$configCache = new Cache\Cache();
		$uid         = 345;

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->set($uid, $cat, $key, $value);
			}
		}

		self::assertConfigValues($data, $configCache, $uid);
	}


	/**
	 * Test the getP() method with a category
	 */
	public function testGetCat()
	{
		$configCache = new Cache\Cache();
		$uid         = 345;

		$configCache->load($uid, [
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
		], $configCache->get($uid, 'system'));

		// test explicit cat with null as key
		self::assertEquals([
			'key1' => 'value1',
			'key2' => 'value2',
		], $configCache->get($uid, 'system', null));
	}

	/**
	 * Test the deleteP() method
	 *
	 * @dataProvider dataTests
	 */
	public function testDelete($data)
	{
		$configCache = new Cache\Cache();
		$uid         = 345;

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->set($uid, $cat, $key, $value);
			}
		}

		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				$configCache->delete($uid, $cat, $key);
			}
		}

		self::assertEmpty($configCache->getAll());
	}

	/**
	 * Test the keyDiff() method with result
	 */
	public function testKeyDiffWithResult()
	{
		$configCache = new Cache\Cache();

		$diffConfig = [
			'fakeCat' => [
				'fakeKey' => 'value',
			]
		];

		self::assertEquals($diffConfig, $configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the keyDiff() method without result
	 *
	 * @dataProvider dataTests
	 */
	public function testKeyDiffWithoutResult($data)
	{
		$configCache = new Cache\Cache();

		$configCache->load(1, $data);

		$diffConfig = $configCache->getAll();

		self::assertEmpty($configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the default hiding of passwords inside the cache
	 */
	public function testPasswordHide()
	{
		$configCache = new Cache\Cache();

		$configCache->load(1, [
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			]
		]);

		self::assertEquals('supersecure', $configCache->get(1, 'database', 'password'));
		self::assertNotEquals('supersecure', print_r($configCache->get(1, 'database', 'password'), true));
		self::assertEquals('notsecured', print_r($configCache->get(1, 'database', 'username'), true));
	}

	/**
	 * Test disabling the hiding of passwords inside the cache
	 */
	public function testPasswordShow()
	{
		$configCache = new Cache\Cache(false);

		$configCache->load(1, [
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			]
		]);

		self::assertEquals('supersecure', $configCache->get(1, 'database', 'password'));
		self::assertEquals('supersecure', print_r($configCache->get(1, 'database', 'password'), true));
		self::assertEquals('notsecured', print_r($configCache->get(1, 'database', 'username'), true));
	}

	/**
	 * Test a empty password
	 */
	public function testEmptyPassword()
	{
		$configCache = new Cache\Cache();

		$configCache->load(1, [
			'database' => [
				'password' => '',
				'username' => '',
			]
		]);

		self::assertEmpty($configCache->get(1, 'database', 'password'));
		self::assertEmpty($configCache->get(1, 'database', 'username'));
	}

	public function testWrongTypePassword()
	{
		$configCache = new Cache\Cache();

		$configCache->load(1, [
			'database' => [
				'password' => new \stdClass(),
				'username' => '',
			]
		]);

		self::assertNotEmpty($configCache->get(1, 'database', 'password'));
		self::assertEmpty($configCache->get(1, 'database', 'username'));

		$configCache = new Cache\Cache();

		$configCache->load(1, [
			'database' => [
				'password' => 23,
				'username' => '',
			],
		]);

		self::assertEquals(23, $configCache->get(1, 'database', 'password'));
		self::assertEmpty($configCache->get(1, 'database', 'username'));
	}

	/**
	 * Test two different UID configs and make sure that there is no overlapping possible
	 */
	public function testTwoUid()
	{
		$configCache = new Cache\Cache();

		$configCache->load(1, [
			'cat1' => [
				'key1' => 'value1',
			],
		]);


		$configCache->load(2, [
			'cat2' => [
				'key2' => 'value2',
			],
		]);

		self::assertEquals('value1', $configCache->get(1, 'cat1', 'key1'));
		self::assertEquals('value2', $configCache->get(2, 'cat2', 'key2'));

		self::assertNull($configCache->get(1, 'cat2', 'key2'));
		self::assertNull($configCache->get(2, 'cat1', 'key1'));
	}

	/**
	 * Test when using an invalid UID
	 * @todo check it the clean way before using the config class
	 */
	public function testInvalidUid()
	{
		// bad UID!
		$uid = null;

		$configCache = new Cache\Cache();

		self::assertNull($configCache->get($uid, 'cat1', 'cat2'));

		self::assertFalse($configCache->set($uid, 'cat1', 'key1', 'doesn\'t matter!'));
		self::assertFalse($configCache->delete($uid, 'cat1', 'key1'));
	}
}
