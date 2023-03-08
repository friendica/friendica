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

namespace Friendica\Test\src\Core\KeyValueStorage;

use Friendica\Core\KeyValueStorage\Capabilities\IManageKeyValuePairs;
use Friendica\Test\MockedTest;

abstract class KeyValueStorageTest extends MockedTest
{
	abstract public function getInstance(): IManageKeyValuePairs;

	public function testInstance()
	{
		$instance = $this->getInstance();

		self::assertInstanceOf(IManageKeyValuePairs::class, $instance);
	}

	public function dataTests(): array
	{
		return [
			'string'       => ['k' => 'data', 'v' => 'it'],
			'boolTrue'     => ['k' => 'data', 'v' => true],
			'boolFalse'    => ['k' => 'data', 'v' => false],
			'integer'      => ['k' => 'data', 'v' => 235],
			'decimal'      => ['k' => 'data', 'v' => 2.456],
			'array'        => ['k' => 'data', 'v' => ['1', 2, '3', true, false]],
			'boolIntTrue'  => ['k' => 'data', 'v' => 1],
			'boolIntFalse' => ['k' => 'data', 'v' => 0],
		];
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testGetSetDelete($k, $v)
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$instance->delete($k);

		self::assertNull($instance->get($k));
		self::assertNull($instance[$k]);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetOverride($k, $v)
	{
		$instance = $this->getInstance();

		$instance->set($k, $v);

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		$instance->set($k, 'another_value');

		self::assertEquals('another_value', $instance->get($k));
		self::assertEquals('another_value', $instance[$k]);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testOffsetSetDelete($k, $v)
	{
		$instance = $this->getInstance();

		$instance[$k] = $v;

		self::assertEquals($v, $instance->get($k));
		self::assertEquals($v, $instance[$k]);

		unset($instance[$k]);

		self::assertNull($instance->get($k));
		self::assertNull($instance[$k]);
	}
}
