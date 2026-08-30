<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Friendica\Core\Cache\Capability\ICanCache;

abstract class CacheTestCase extends MockedTestCase
{
	/**
	 * @var int Start time of the mock (used for time operations)
	 */
	protected $startTime = 1417011228;

	/**
	 * @var ICanCache
	 */
	protected $instance;

	/**
	 * @var \Friendica\Core\Cache\Capability\ICanCache
	 */
	protected $cache;

	/**
	 * Dataset for test setting different types in the cache
	 *
	 * @return array
	 */
	public static function dataTypesInCache()
	{
		return [
			'string'    => ['data' => 'foobar'],
			'integer'   => ['data' => 1],
			'boolTrue'  => ['data' => true],
			'boolFalse' => ['data' => false],
			'float'     => ['data' => 4.6634234],
			'array'     => ['data' => ['1', '2', '3', '4', '5']],
			'object'    => ['data' => new \stdClass()],
			'null'      => ['data' => null],
		];
	}

	/**
	 * Dataset for simple value sets/gets
	 *
	 * @return array
	 */
	public static function dataSimple()
	{
		return [
			'string' => [
				'value1' => 'foobar',
				'value2' => 'ipsum lorum',
				'value3' => 'test',
				'value4' => 'lasttest',
			],
		];
	}

	abstract protected function getInstance();

	protected function setUp(): void
	{
		parent::setUp();

		$this->instance = $this->getInstance();

		$this->instance->clear(false);
	}

	/**
	 * @param mixed $value1 a first
	 * @param mixed $value2 a second
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSimple')]
	public function testSimple($value1, $value2, $value3, $value4): void
	{
		self::assertNull($this->instance->get('value1'));

		$this->instance->set('value1', $value1);
		$received = $this->instance->get('value1');
		self::assertEquals($value1, $received, 'Value received from cache not equal to the original');

		$this->instance->set('value1', $value2);
		$received = $this->instance->get('value1');
		self::assertEquals($value2, $received, 'Value not overwritten by second set');

		$this->instance->set('value2', $value1);
		$received2 = $this->instance->get('value2');
		self::assertEquals($value2, $received, 'Value changed while setting other variable');
		self::assertEquals($value1, $received2, 'Second value not equal to original');

		self::assertNull($this->instance->get('not_set'), 'Unset value not equal to null');

		self::assertTrue($this->instance->delete('value1'));
		self::assertNull($this->instance->get('value1'));
	}

	/**
	 *
	 * @param mixed $value1 a first
	 * @param mixed $value2 a second
	 * @param mixed $value3 a third
	 * @param mixed $value4 a fourth
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSimple')]
	public function testClear($value1, $value2, $value3, $value4): void
	{
		$this->instance->set('1_value1', $value1);
		$this->instance->set('1_value2', $value2);
		$this->instance->set('2_value1', $value3);
		$this->instance->set('3_value1', $value4);

		self::assertEquals([
			'1_value1' => $value1,
			'1_value2' => $value2,
			'2_value1' => $value3,
			'3_value1' => $value4,
		], [
			'1_value1' => $this->instance->get('1_value1'),
			'1_value2' => $this->instance->get('1_value2'),
			'2_value1' => $this->instance->get('2_value1'),
			'3_value1' => $this->instance->get('3_value1'),
		]);

		self::assertTrue($this->instance->clear());

		self::assertEquals([
			'1_value1' => $value1,
			'1_value2' => $value2,
			'2_value1' => $value3,
			'3_value1' => $value4,
		], [
			'1_value1' => $this->instance->get('1_value1'),
			'1_value2' => $this->instance->get('1_value2'),
			'2_value1' => $this->instance->get('2_value1'),
			'3_value1' => $this->instance->get('3_value1'),
		]);

		self::assertTrue($this->instance->clear(false));

		self::assertEquals([
			'1_value1' => null,
			'1_value2' => null,
			'2_value3' => null,
			'3_value4' => null,
		], [
			'1_value1' => $this->instance->get('1_value1'),
			'1_value2' => $this->instance->get('1_value2'),
			'2_value3' => $this->instance->get('2_value3'),
			'3_value4' => $this->instance->get('3_value4'),
		]);
	}

	public function testTTL(): void
	{
		static::markTestSkipped('taking too much time without mocking');

		self::assertNull($this->instance->get('value1')); // @phpstan-ignore deadCode.unreachable (skipped test)

		$value = 'foobar';
		$this->instance->set('value1', $value, 1);
		$received = $this->instance->get('value1');
		self::assertEquals($value, $received, 'Value received from cache not equal to the original');

		sleep(2);

		self::assertNull($this->instance->get('value1'));
	}

	/**
	 * @param mixed $data the data to store in the cache
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTypesInCache')]
	public function testDifferentTypesInCache($data): void
	{
		$this->instance->set('val', $data);
		$received = $this->instance->get('val');
		self::assertEquals($data, $received, 'Value type changed from ' . gettype($data) . ' to ' . gettype($received));
	}

	/**
	 * @param mixed $value1 a first
	 * @param mixed $value2 a second
	 * @param mixed $value3 a third
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSimple')]
	public function testGetAllKeys($value1, $value2, $value3, $value4): void
	{
		self::assertTrue($this->instance->set('value1', $value1));
		self::assertTrue($this->instance->set('value2', $value2));
		self::assertTrue($this->instance->set('test_value3', $value3));

		$list = $this->instance->getAllKeys();

		self::assertContains('value1', $list);
		self::assertContains('value2', $list);
		self::assertContains('test_value3', $list);

		$list = $this->instance->getAllKeys('test');

		self::assertContains('test_value3', $list);
		self::assertNotContains('value1', $list);
		self::assertNotContains('value2', $list);
	}

	public function testSpaceInKey(): void
	{
		self::assertTrue($this->instance->set('key space', 'value'));
		self::assertEquals('value', $this->instance->get('key space'));
	}

	public function testGetName(): void
	{
		if (defined($this->instance::class . '::NAME')) {
			self::assertEquals($this->instance::NAME, $this->instance->getName());
		} else {
			self::expectNotToPerformAssertions();
		}
	}
}
