<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content\Text;

use Exception;
use Friendica\Content\Text\Markdown;
use Friendica\Test\FixtureTestCase;

class MarkdownTest extends FixtureTestCase
{
	public function dataMarkdown()
	{
		$inputFiles = glob(__DIR__ . '/../../../datasets/content/text/markdown/*.md');

		$data = [];

		foreach ($inputFiles as $file) {
			$data[str_replace('.md', '', $file)] = [
				'input'    => file_get_contents($file),
				'expected' => file_get_contents(str_replace('.md', '.html', $file))
			];
		}

		return $data;
	}

	/**
	 * Test convert different input Markdown text into HTML
	 *
	 * @dataProvider dataMarkdown
	 *
	 * @param string $input    The Markdown text to test
	 * @param string $expected The expected HTML output
	 *
	 * @throws Exception
	 */
	public function testConvert(string $input, string $expected)
	{
		$output = Markdown::convert($input);

		self::assertEquals($expected, $output);
	}

	public function dataMarkdownText()
	{
		return [
			'bug-8358-double-decode' => [
				'expectedBBCode' => 'with the <sup> and </sup> tag',
				'markdown'       => 'with the &lt;sup&gt; and &lt;/sup&gt; tag',
			],
		];
	}

	/**
	 * Test convert Markdown to BBCode
	 *
	 * @dataProvider dataMarkdownText
	 *
	 * @param string $expectedBBCode Expected BBCode output
	 * @param string $html           Markdown text
	 */
	public function testToBBCode(string $expectedBBCode, string $html)
	{
		$actual = Markdown::toBBCode($html);

		self::assertEquals($expectedBBCode, $actual);
	}
}
