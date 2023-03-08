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

use Friendica\Util\XML;
use PHPUnit\Framework\TestCase;

/**
 * XML utility test class
 */
class XmlTest extends TestCase
{
	/**
	 * escape and unescape
	 */
	public function testEscapeUnescape()
	{
		$text   = "<tag>I want to break\n this!11!<?hard?></tag>";
		$xml    = XML::escape($text);
		$retext = XML::unescape($text);
		self::assertEquals($text, $retext);
	}

	/**
	 * escape and put in a document
	 */
	public function testEscapeDocument()
	{
		$tag        = "<tag>I want to break</tag>";
		$xml        = XML::escape($tag);
		$text       = '<text>' . $xml . '</text>';
		$xml_parser = xml_parser_create();
		//should be possible to parse it
		$values = [];
		$index  = [];
		self::assertEquals(1, xml_parse_into_struct($xml_parser, $text, $values, $index));
		self::assertEquals(
			['TEXT' => [0]],
			$index
		);
		self::assertEquals(
			[['tag' => 'TEXT', 'type' => 'complete', 'level' => 1, 'value' => $tag]],
			$values
		);
		xml_parser_free($xml_parser);
	}
}
