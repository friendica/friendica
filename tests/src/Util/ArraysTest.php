<?php
/**
 * @file tests/src/Util/Arrays.php
 * @author Roland Haeder<https://f.haeder.net/profile/roland>
 */
namespace Friendica\Test\Util;

use Friendica\Util\Arrays;
use PHPUnit\Framework\TestCase;

/**
 * @brief Array utility testing class
 */
class ArraysTest extends TestCase
{
	/**
	 * @brief Tests if an empty array and an empty delimiter returns an empty string.
	 */
	public function testEmptyArrayEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([], '');
		$this->assertEmpty($str);
	}

	/**
	 * @brief Tests if an empty array and a non-empty delimiter returns an empty string.
	 */
	public function testEmptyArrayNonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([], ',');
		$this->assertEmpty($str);
	}

	/**
	 * @brief Tests if a non-empty array and an empty delimiter returns the value (1).
	 */
	public function testNonEmptyArrayEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1], '');
		$this->assertSame($str, '1');
	}

	/**
	 * @brief Tests if a non-empty array and an empty delimiter returns the value (12).
	 */
	public function testNonEmptyArray2EmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1, 2], '');
		$this->assertSame($str, '12');
	}

	/**
	 * @brief Tests if a non-empty array and a non-empty delimiter returns the value (1).
	 */
	public function testNonEmptyArrayNonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1], ',');
		$this->assertSame($str, '1');
	}

	/**
	 * @brief Tests if a non-empty array and a non-empty delimiter returns the value (1,2).
	 */
	public function testNonEmptyArray2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([1, 2], ',');
		$this->assertSame($str, '1,2');
	}

	/**
	 * @brief Tests if a 2-dim array and an empty delimiter returns the expected string.
	 */
	public function testEmptyMultiArray2EmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], []], '');
		$this->assertSame($str, '{1}{}');
	}

	/**
	 * @brief Tests if a 2-dim array and an empty delimiter returns the expected string.
	 */
	public function testEmptyMulti2Array2EmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], [2]], '');
		$this->assertSame($str, '{1}{2}');
	}

	/**
	 * @brief Tests if a 2-dim array and a non-empty delimiter returns the expected string.
	 */
	public function testEmptyMultiArray2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], []], ',');
		$this->assertSame($str, '{1},{}');
	}

	/**
	 * @brief Tests if a 2-dim array and a non-empty delimiter returns the expected string.
	 */
	public function testEmptyMulti2Array2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], [2]], ',');
		$this->assertSame($str, '{1},{2}');
	}

	/**
	 * @brief Tests if a 3-dim array and a non-empty delimiter returns the expected string.
	 */
	public function testEmptyMulti3Array2NonEmptyDelimiter()
	{
		$str = Arrays::recursiveImplode([[1], [2, [3]]], ',');
		$this->assertSame($str, '{1},{2,{3}}');
	}
}
