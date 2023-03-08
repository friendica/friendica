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

namespace Friendica\Test\src\Util;

use Friendica\Util\Arrays;
use PHPUnit\Framework\TestCase;

/**
 * Array utility testing class
 */
class ArraysTest extends TestCase
{
	/**
	 * Tests if an empty array and an empty delimiter returns an empty string.
	 */
	public function testEmptyArrayEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([], '');
		self::assertEmpty($str);
	}

	/**
	 * Tests if an empty array and a non-empty delimiter returns an empty string.
	 */
	public function testEmptyArrayNonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([], ',');
		self::assertEmpty($str);
	}

	/**
	 * Tests if a non-empty array and an empty delimiter returns the value (1).
	 */
	public function testNonEmptyArrayEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1], '');
		self::assertSame($str, '1');
	}

	/**
	 * Tests if a non-empty array and an empty delimiter returns the value (12).
	 */
	public function testNonEmptyArray2EmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1, 2], '');
		self::assertSame($str, '12');
	}

	/**
	 * Tests if a non-empty array and a non-empty delimiter returns the value (1).
	 */
	public function testNonEmptyArrayNonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1], ',');
		self::assertSame($str, '1');
	}

	/**
	 * Tests if a non-empty array and a non-empty delimiter returns the value (1,2).
	 */
	public function testNonEmptyArray2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1, 2], ',');
		self::assertSame($str, '1,2');
	}

	/**
	 * Tests if a 2-dim array and an empty delimiter returns the expected string.
	 */
	public function testEmptyMultiArray2EmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], []], '');
		self::assertSame($str, '{1}{}');
	}

	/**
	 * Tests if a 2-dim array and an empty delimiter returns the expected string.
	 */
	public function testEmptyMulti2Array2EmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], [2]], '');
		self::assertSame($str, '{1}{2}');
	}

	/**
	 * Tests if a 2-dim array and a non-empty delimiter returns the expected string.
	 */
	public function testEmptyMultiArray2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], []], ',');
		self::assertSame($str, '{1},{}');
	}

	/**
	 * Tests if a 2-dim array and a non-empty delimiter returns the expected string.
	 */
	public function testEmptyMulti2Array2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], [2]], ',');
		self::assertSame($str, '{1},{2}');
	}

	/**
	 * Tests if a 3-dim array and a non-empty delimiter returns the expected string.
	 */
	public function testEmptyMulti3Array2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], [2, [3]]], ',');
		self::assertSame($str, '{1},{2,{3}}');
	}

	/**
	 * Test the Arrays::walkRecursive() function.
	 */
	public function testApiWalkRecursive()
	{
		$array = ['item1'];
		self::assertEquals(
			$array,
			Arrays::walkRecursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}

	/**
	 * Test the Arrays::walkRecursive() function with an array.
	 *
	 * @return void
	 */
	public function testApiWalkRecursiveWithArray()
	{
		$array = [['item1'], ['item2']];
		self::assertEquals(
			$array,
			Arrays::walkRecursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}
}
