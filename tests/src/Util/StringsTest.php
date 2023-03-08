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

use Friendica\Util\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Strings utility test class
 */
class StringsTest extends TestCase
{
	/**
	 * randomnames should be random, even length
	 */
	public function testRandomEven()
	{
		$randomname1 = Strings::getRandomName(10);
		$randomname2 = Strings::getRandomName(10);

		self::assertNotEquals($randomname1, $randomname2);
	}

	/**
	 * randomnames should be random, odd length
	 */
	public function testRandomOdd()
	{
		$randomname1 = Strings::getRandomName(9);
		$randomname2 = Strings::getRandomName(9);

		self::assertNotEquals($randomname1, $randomname2);
	}

	/**
	 * try to fail ramdonnames
	 */
	public function testRandomNameNoLength()
	{
		$randomname1 = Strings::getRandomName(0);
		self::assertEquals(0, strlen($randomname1));
	}

	/**
	 * try to fail it with invalid input
	 *
	 * @todo What's corect behaviour here? An exception?
	 */
	public function testRandomNameNegativeLength()
	{
		$randomname1 = Strings::getRandomName(-23);
		self::assertEquals(0, strlen($randomname1));
	}

	/**
	 * test with a length, that may be too short
	 */
	public function testRandomNameLength1()
	{
		$randomname1 = Strings::getRandomName(1);
		self::assertEquals(1, strlen($randomname1));

		$randomname2 = Strings::getRandomName(1);
		self::assertEquals(1, strlen($randomname2));
	}

	/**
	 * test, that tags are escaped
	 */
	public function testEscapeHtml()
	{
		$invalidstring='<submit type="button" onclick="alert(\'failed!\');" />';

		$escapedString = Strings::escapeHtml($invalidstring);

		self::assertEquals(
			"&lt;submit type=&quot;button&quot; onclick=&quot;alert('failed!');&quot; /&gt;",
			$escapedString
		);
	}

	public function dataIsHex()
	{
		return [
			'validHex' => [
				'input' => '90913473615bf00c122ac78338492980',
				'valid' => true,
			],
			'invalidHex' => [
				'input' => '90913473615bf00c122ac7833849293',
				'valid' => false,
			],
			'emptyHex' => [
				'input' => '',
				'valid' => false,
			],
		];
	}

	/**
	 * Tests if the string is a valid hexadecimal value
	 *
	 * @param string $input Input string
	 * @param bool   $valid Whether testing on valid or invalid
	 *
	 * @dataProvider dataIsHex
	 */
	public function testIsHex(string $input, bool $valid = false)
	{
		self::assertEquals($valid, Strings::isHex($input));
	}

	/**
	 * Tests that Strings::substringReplace behaves the same as substr_replace with ASCII strings in all the possible
	 * numerical parameter configurations (positive, negative, zero, out of bounds either side, null)
	 */
	public function testSubstringReplaceASCII()
	{
		for ($start = -10; $start <= 10; $start += 5) {
			self::assertEquals(
				substr_replace('string', 'replacement', $start),
				Strings::substringReplace('string', 'replacement', $start)
			);

			for ($length = -10; $length <= 10; $length += 5) {
				self::assertEquals(
					substr_replace('string', 'replacement', $start, $length),
					Strings::substringReplace('string', 'replacement', $start, $length)
				);
			}
		}
	}


	public function dataSubstringReplaceMultiByte()
	{
		return [
			'issue-8470' => [
				'expected' => 'Je n’y pense que maintenant (pask ma sonnette ne fonctionne pas) : mettre un gentil mot avec mes coordonnées sur ma porte est le moyen le plus simple de rester en contact si besoin avec mon voisinage direct ! [url=https://www.instagram.com/p/B-UdH2loee1/?igshid=x4aglyju9kva]instagram.com/p/B-UdH2loee1/…[/url] [rest of the post]',
				'string' => 'Je n’y pense que maintenant (pask ma sonnette ne fonctionne pas) : mettre un gentil mot avec mes coordonnées sur ma porte est le moyen le plus simple de rester en contact si besoin avec mon voisinage direct ! https://t.co/YoBWTHsAAk [rest of the post]',
				'replacement' => '[url=https://www.instagram.com/p/B-UdH2loee1/?igshid=x4aglyju9kva]instagram.com/p/B-UdH2loee1/…[/url]',
				'start' => 209,
				'length' => 23,
			],
		];
	}

	/**
	 * Tests cases where Strings::substringReplace is needed over substr_replace with multi-byte strings and character
	 * offsets
	 *
	 * @param string   $expected
	 * @param string   $string
	 * @param string   $replacement
	 * @param int      $start
	 * @param int|null $length
	 *
	 * @dataProvider dataSubstringReplaceMultiByte
	 */
	public function testSubstringReplaceMultiByte(string $expected, string $string, string $replacement, int $start, int $length = null)
	{
		self::assertEquals(
			$expected,
			Strings::substringReplace(
				$string,
				$replacement,
				$start,
				$length
			)
		);
	}

	public function testPerformWithEscapedBlocks()
	{
		$originalText = '[noparse][/noparse][nobb]nobb[/nobb][noparse]noparse[/noparse]';

		$text = Strings::performWithEscapedBlocks($originalText, '#[(?:noparse|nobb)].*?\[/(?:noparse|nobb)]#is', function ($text) {
			return $text;
		});

		self::assertEquals($originalText, $text);
	}

	public function testPerformWithEscapedBlocksNested()
	{
		$originalText = '[noparse][/noparse][nobb]nobb[/nobb][noparse]noparse[/noparse]';

		$text = Strings::performWithEscapedBlocks($originalText, '#[nobb].*?\[/nobb]#is', function ($text) {
			$text = Strings::performWithEscapedBlocks($text, '#[noparse].*?\[/noparse]#is', function ($text) {
				return $text;
			});

			return $text;
		});

		self::assertEquals($originalText, $text);
	}
}
