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

namespace Friendica\Test\src\Core\Cache;

use Exception;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;

abstract class MemoryCacheTest extends CacheTest
{
	/**
	 * @var \Friendica\Core\Cache\Capability\ICanCacheInMemory
	 */
	protected $instance;

	protected function setUp(): void
	{
		parent::setUp();

		if (!($this->instance instanceof ICanCacheInMemory)) {
			throw new Exception('MemoryCacheTest unsupported');
		}
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	public function testCompareSet($value1, $value2)
	{
		self::assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $value1);
		$received = $this->instance->get('value1');
		self::assertEquals($value1, $received, 'Value received from cache not equal to the original');

		$this->instance->compareSet('value1', $value1, $value2);
		$received = $this->instance->get('value1');
		self::assertEquals($value2, $received, 'Value not overwritten by compareSet');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	public function testNegativeCompareSet($value1, $value2)
	{
		self::assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $value1);
		$received = $this->instance->get('value1');
		self::assertEquals($value1, $received, 'Value received from cache not equal to the original');

		$this->instance->compareSet('value1', 'wrong', $value2);
		$received = $this->instance->get('value1');
		self::assertNotEquals($value2, $received, 'Value was wrongly overwritten by compareSet');
		self::assertEquals($value1, $received, 'Value was wrongly overwritten by any other value');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	public function testCompareDelete($data)
	{
		self::assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $data);
		$received = $this->instance->get('value1');
		self::assertEquals($data, $received, 'Value received from cache not equal to the original');
		$this->instance->compareDelete('value1', $data);
		self::assertNull($this->instance->get('value1'), 'Value was not deleted by compareDelete');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	public function testNegativeCompareDelete($data)
	{
		self::assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $data);
		$received = $this->instance->get('value1');
		self::assertEquals($data, $received, 'Value received from cache not equal to the original');
		$this->instance->compareDelete('value1', 'wrong');
		self::assertNotNull($this->instance->get('value1'), 'Value was wrongly compareDeleted');

		$this->instance->compareDelete('value1', $data);
		self::assertNull($this->instance->get('value1'), 'Value was wrongly NOT deleted by compareDelete');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	public function testAdd($value1, $value2)
	{
		self::assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $value1);

		$this->instance->add('value1', $value2);
		$received = $this->instance->get('value1');
		self::assertNotEquals($value2, $received, 'Value was wrongly overwritten by add');
		self::assertEquals($value1, $received, 'Value was wrongly overwritten by any other value');

		$this->instance->delete('value1');
		$this->instance->add('value1', $value2);
		$received = $this->instance->get('value1');
		self::assertEquals($value2, $received, 'Value was not overwritten by add');
		self::assertNotEquals($value1, $received, 'Value was not overwritten by any other value');
	}
}
