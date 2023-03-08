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

namespace Friendica\Test\src\Content\Text;

use Exception;
use Friendica\Content\Text\Markdown;
use Friendica\Test\FixtureTest;

class MarkdownTest extends FixtureTest
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
				'markdown' => 'with the &lt;sup&gt; and &lt;/sup&gt; tag',
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
