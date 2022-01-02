<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Content\Text\HTML;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\FixtureTest;

class HTMLTest extends FixtureTest
{
	public function dataHTML()
	{
		$inputFiles = glob(__DIR__ . '/../../../datasets/content/text/html/*.html');

		$data = [];

		foreach ($inputFiles as $file) {
			$data[str_replace('.html', '', $file)] = [
				'input'    => file_get_contents($file),
				'expected' => file_get_contents(str_replace('.html', '.txt', $file))
			];
		}

		return $data;
	}

	/**
	 * Test convert different input Markdown text into HTML
	 *
	 * @dataProvider dataHTML
	 *
	 * @param string $input    The Markdown text to test
	 * @param string $expected The expected HTML output
	 *
	 * @throws Exception
	 */
	public function testToPlaintext(string $input, string $expected)
	{
		$output = HTML::toPlaintext($input, 0);

		self::assertEquals($expected, $output);
	}

	public function dataHTMLText()
	{
		return [
			'bug-7665-audio-tag' => [
				'expectedBBCode' => '[audio]http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3[/audio]',
				'html' => '<audio src="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3" controls="controls"><a href="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3">http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3</a></audio>',
			],
			'bug-8075-html-tags' => [
				'expectedBBCode' => "<big rant here> I don't understand tests",
				'html' => "&lt;big rant here&gt; I don't understand tests",
			],
			'bug-10877-code-entities' => [
				'expectedBBCode' => "Now playing
[code]
echo \"main(i){for(i=0;;i++)putchar(((i*(i>>8|i>>9)&46&i>>8))^(i&i>>13|i>>6));}\" | gcc -o a.out -x c - 2> /dev/null
./a.out | aplay -q 2> /dev/null
[/code]
its surprisingly good",
				'html' => "<p>Now playing</p><pre><code>echo &quot;main(i){for(i=0;;i++)putchar(((i*(i&gt;&gt;8|i&gt;&gt;9)&amp;46&amp;i&gt;&gt;8))^(i&amp;i&gt;&gt;13|i&gt;&gt;6));}&quot; | gcc -o a.out -x c - 2&gt; /dev/null
./a.out | aplay -q 2&gt; /dev/null</code></pre><p>its surprisingly good</p>",
			],
		];
	}

	/**
	 * Test convert bbcodes to HTML
	 *
	 * @dataProvider dataHTMLText
	 *
	 * @param string $expectedBBCode Expected BBCode output
	 * @param string $html           HTML text
	 *
	 * @throws InternalServerErrorException
	 */
	public function testToBBCode(string $expectedBBCode, string $html)
	{
		$actual = HTML::toBBCode($html);

		self::assertEquals($expectedBBCode, $actual);
	}
}
