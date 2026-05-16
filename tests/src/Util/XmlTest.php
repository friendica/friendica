<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
			$index,
		);
		self::assertEquals(
			[['tag' => 'TEXT', 'type' => 'complete', 'level' => 1, 'value' => $tag]],
			$values,
		);
	}
}
