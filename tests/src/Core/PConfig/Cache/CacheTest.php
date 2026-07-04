<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\PConfig\Cache;

use Friendica\Test\MockedTestCase;

class CacheTest extends MockedTestCase
{
	public static function dataTests()
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
				],
			],
		];
	}

	private function assertConfigValues($data, \Friendica\Core\PConfig\ValueObject\Cache $configCache, $uid)
	{
		foreach ($data as $cat => $values) {
			foreach ($values as $key => $value) {
				self::assertEquals($data[$cat][$key], $configCache->get($uid, $cat, $key));
			}
		}
	}

	/**
	 * Test the setP() and getP() methods
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testSetGet($data): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();
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
	public function testGetCat(): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();
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
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testDelete($data): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();
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
	public function testKeyDiffWithResult(): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();

		$diffConfig = [
			'fakeCat' => [
				'fakeKey' => 'value',
			],
		];

		self::assertEquals($diffConfig, $configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the keyDiff() method without result
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testKeyDiffWithoutResult($data): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();

		$configCache->load(1, $data);

		$diffConfig = $configCache->getAll();

		self::assertEmpty($configCache->keyDiff($diffConfig));
	}

	/**
	 * Test the default hiding of passwords inside the cache
	 */
	public function testPasswordHide(): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();

		$configCache->load(1, [
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			],
		]);

		self::assertEquals('supersecure', $configCache->get(1, 'database', 'password'));
		self::assertNotEquals('supersecure', print_r($configCache->get(1, 'database', 'password'), true));
		self::assertEquals('notsecured', print_r($configCache->get(1, 'database', 'username'), true));
	}

	/**
	 * Test disabling the hiding of passwords inside the cache
	 */
	public function testPasswordShow(): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache(false);

		$configCache->load(1, [
			'database' => [
				'password' => 'supersecure',
				'username' => 'notsecured',
			],
		]);

		self::assertEquals('supersecure', $configCache->get(1, 'database', 'password'));
		self::assertEquals('supersecure', print_r($configCache->get(1, 'database', 'password'), true));
		self::assertEquals('notsecured', print_r($configCache->get(1, 'database', 'username'), true));
	}

	/**
	 * Test a empty password
	 */
	public function testEmptyPassword(): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();

		$configCache->load(1, [
			'database' => [
				'password' => '',
				'username' => '',
			],
		]);

		self::assertEmpty($configCache->get(1, 'database', 'password'));
		self::assertEmpty($configCache->get(1, 'database', 'username'));
	}

	public function testWrongTypePassword(): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();

		$configCache->load(1, [
			'database' => [
				'password' => new \stdClass(),
				'username' => '',
			],
		]);

		self::assertNotEmpty($configCache->get(1, 'database', 'password'));
		self::assertEmpty($configCache->get(1, 'database', 'username'));

		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();

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
	public function testTwoUid(): void
	{
		$configCache = new \Friendica\Core\PConfig\ValueObject\Cache();

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
}
