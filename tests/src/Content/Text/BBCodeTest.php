<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\App\BaseURL;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\VFSTrait;
use Mockery;

class BBCodeTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	protected function setUp()
	{
		parent::setUp();
		$this->setUpVfsDir();
		$this->mockApp($this->root);
		$this->app->videowidth = 425;
		$this->app->videoheight = 350;
		$this->configMock->shouldReceive('get')
			->with('system', 'remove_multiplicated_lines')
			->andReturn(false);
		$this->configMock->shouldReceive('get')
			->with('system', 'no_oembed')
			->andReturn(false);
		$this->configMock->shouldReceive('get')
			->with('system', 'allowed_link_protocols')
			->andReturn(null);
		$this->configMock->shouldReceive('get')
			->with('system', 'itemcache_duration')
			->andReturn(-1);
		$this->configMock->shouldReceive('get')
			->with('system', 'url')
			->andReturn('friendica.local');
		$this->configMock->shouldReceive('get')
			->with('system', 'no_smilies')
			->andReturn(false);
		$this->configMock->shouldReceive('get')
			->with('system', 'big_emojis')
			->andReturn(false);
		$this->configMock->shouldReceive('get')
			->with('system', 'no_oembed_rich_content')
			->andReturn(false);
		$this->configMock->shouldReceive('get')
			->with('system', 'allowed_oembed')
			->andReturn('');

		$l10nMock = Mockery::mock(L10n::class);
		$l10nMock->shouldReceive('t')->withAnyArgs()->andReturnUsing(function ($args) { return $args; });
		$this->dice->shouldReceive('create')
		           ->with(L10n::class)
		           ->andReturn($l10nMock);

		$baseUrlMock = Mockery::mock(BaseURL::class);
		$baseUrlMock->shouldReceive('get')->withAnyArgs()->andReturn('friendica.local');
		$this->dice->shouldReceive('create')
		           ->with(BaseURL::class)
		           ->andReturn($baseUrlMock);

		$config = \HTMLPurifier_HTML5Config::createDefault();
		$config->set('HTML.Doctype', 'HTML5');
		$config->set('Attr.AllowedRel', [
			'noreferrer' => true,
			'noopener' => true,
		]);
		$config->set('Attr.AllowedFrameTargets', [
			'_blank' => true,
		]);

		$this->HTMLPurifier = new \HTMLPurifier($config);
	}

	public function dataLinks()
	{
		return [
			/** @see https://github.com/friendica/friendica/issues/2487 */
			'bug-2487-1' => [
				'data' => 'https://de.wikipedia.org/wiki/Juha_Sipilä',
				'assertHTML' => true,
			],
			'bug-2487-2' => [
				'data' => 'https://de.wikipedia.org/wiki/Dnepr_(Motorradmarke)',
				'assertHTML' => true,
			],
			'bug-2487-3' => [
				'data' => 'https://friendica.wäckerlin.ch/friendica',
				'assertHTML' => true,
			],
			'bug-2487-4' => [
				'data' => 'https://mastodon.social/@morevnaproject',
				'assertHTML' => true,
			],
			/** @see https://github.com/friendica/friendica/issues/5795 */
			'bug-5795' => [
				'data' => 'https://social.nasqueron.org/@liw/100798039015010628',
				'assertHTML' => true,
			],
			/** @see https://github.com/friendica/friendica/issues/6095 */
			'bug-6095' => [
				'data' => 'https://en.wikipedia.org/wiki/Solid_(web_decentralization_project)',
				'assertHTML' => true,
			],
			'no-protocol' => [
				'data' => 'example.com/path',
				'assertHTML' => false
			],
			'wrong-protocol' => [
				'data' => 'ftp://example.com',
				'assertHTML' => false
			],
			'wrong-domain-without-path' => [
				'data' => 'http://example',
				'assertHTML' => false
			],
			'wrong-domain-with-path' => [
				'data' => 'http://example/path',
				'assertHTML' => false
			],
			'bug-6857-domain-start' => [
				'data' => "http://\nexample.com",
				'assertHTML' => false
			],
			'bug-6857-domain-end' => [
				'data' => "http://example\n.com",
				'assertHTML' => false
			],
			'bug-6857-tld' => [
				'data' => "http://example.\ncom",
				'assertHTML' => false
			],
			'bug-6857-end' => [
				'data' => "http://example.com\ntest",
				'assertHTML' => false
			],
			'bug-6901' => [
				'data' => "http://example.com<ul>",
				'assertHTML' => false
			],
			'bug-7150' => [
				'data' => html_entity_decode('http://example.com&nbsp;', ENT_QUOTES, 'UTF-8'),
				'assertHTML' => false
			],
			'bug-7271-query-string-brackets' => [
				'data' => 'https://example.com/search?q=square+brackets+[url]',
				'assertHTML' => true
			],
			'bug-7271-path-brackets' => [
				'data' => 'http://example.com/path/to/file[3].html',
				'assertHTML' => true
			],
		];
	}

	/**
	 * Test convert different links inside a text
	 *
	 * @dataProvider dataLinks
	 *
	 * @param string $data       The data to text
	 * @param bool   $assertHTML True, if the link is a HTML link (<a href...>...</a>)
	 *
	 * @throws InternalServerErrorException
	 */
	public function testAutoLinking(string $data, bool $assertHTML)
	{
		$output = BBCode::convert($data);
		$assert = $this->HTMLPurifier->purify('<a href="' . $data . '" target="_blank" rel="noopener noreferrer">' . $data . '</a>');
		if ($assertHTML) {
			self::assertEquals($assert, $output);
		} else {
			self::assertNotEquals($assert, $output);
		}
	}

	public function dataBBCodes()
	{
		return [
			'bug-7271-condensed-space' => [
				'expectedHtml' => '<ul class="listdecimal" style="list-style-type:decimal;"><li> <a href="http://example.com/" target="_blank" rel="noopener noreferrer">http://example.com/</a></li></ul>',
				'text' => '[ol][*] http://example.com/[/ol]',
			],
			'bug-7271-condensed-nospace' => [
				'expectedHtml' => '<ul class="listdecimal" style="list-style-type:decimal;"><li><a href="http://example.com/" target="_blank" rel="noopener noreferrer">http://example.com/</a></li></ul>',
				'text' => '[ol][*]http://example.com/[/ol]',
			],
			'bug-7271-indented-space' => [
				'expectedHtml' => '<ul class="listbullet" style="list-style-type:circle;"><li> <a href="http://example.com/" target="_blank" rel="noopener noreferrer">http://example.com/</a></li></ul>',
				'text' => '[ul]
[*] http://example.com/
[/ul]',
			],
			'bug-7271-indented-nospace' => [
				'expectedHtml' => '<ul class="listbullet" style="list-style-type:circle;"><li><a href="http://example.com/" target="_blank" rel="noopener noreferrer">http://example.com/</a></li></ul>',
				'text' => '[ul]
[*]http://example.com/
[/ul]',
			],
			'bug-2199-named-size' => [
				'expectedHtml' => '<span style="font-size:xx-large;line-height:normal;">Test text</span>',
				'text' => '[size=xx-large]Test text[/size]',
			],
			'bug-2199-numeric-size' => [
				'expectedHtml' => '<span style="font-size:24px;line-height:normal;">Test text</span>',
				'text' => '[size=24]Test text[/size]',
			],
			'bug-2199-diaspora-no-named-size' => [
				'expectedHtml' => 'Test text',
				'text' => '[size=xx-large]Test text[/size]',
				'try_oembed' => false,
				// Triggers the diaspora compatible output
				'simpleHtml' => 3,
			],
			'bug-2199-diaspora-no-numeric-size' => [
				'expectedHtml' => 'Test text',
				'text' => '[size=24]Test text[/size]',
				'try_oembed' => false,
				// Triggers the diaspora compatible output
				'simpleHtml' => 3,
			],
			'bug-7665-audio-tag' => [
				'expectedHtml' => '<audio src="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3" controls><a href="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3">http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3</a></audio>',
				'text' => '[audio]http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3[/audio]',
				'try_oembed' => true,
			],
			'bug-7808-code-lt' => [
				'expectedHtml' => '<code>&lt;</code>',
				'text' => '[code]<[/code]',
			],
			'bug-7808-code-gt' => [
				'expectedHtml' => '<code>&gt;</code>',
				'text' => '[code]>[/code]',
			],
			'bug-7808-code-amp' => [
				'expectedHtml' => '<code>&amp;</code>',
				'text' => '[code]&[/code]',
			],
			'task-8800-pre-spaces-notag' => [
				'expectedHtml' => '[test] Space',
				'text' => '[test] Space',
			],
			'task-8800-pre-spaces' => [
				'expectedHtml' => '    Spaces',
				'text' => '[pre]    Spaces[/pre]',
			],
			'bug-9611-purify-xss-nobb' => [
				'expectedHTML' => '<span>dare to move your mouse here</span>',
				'text' => '[nobb]<span onmouseover="alert(0)">dare to move your mouse here</span>[/nobb]'
			],
			'bug-9611-purify-xss-noparse' => [
				'expectedHTML' => '<span>dare to move your mouse here</span>',
				'text' => '[noparse]<span onmouseover="alert(0)">dare to move your mouse here</span>[/noparse]'
			],
			'bug-9611-purify-xss-attributes' => [
				'expectedHTML' => '<span>dare to move your mouse here</span>',
				'text' => '[color="onmouseover=alert(0) style="]dare to move your mouse here[/color]'
			],
			'bug-9611-purify-attributes-correct' => [
				'expectedHTML' => '<span style="color:#FFFFFF;">dare to move your mouse here</span>',
				'text' => '[color=FFFFFF]dare to move your mouse here[/color]'
			],
			'bug-9639-span-classes' => [
				'expectedHTML' => '<span class="arbitrary classes">Test</span>',
				'text' => '[class=arbitrary classes]Test[/class]',
			],
		];
	}

	/**
	 * Test convert bbcodes to HTML
	 *
	 * @dataProvider dataBBCodes
	 *
	 * @param string $expectedHtml Expected HTML output
	 * @param string $text         BBCode text
	 * @param bool   $try_oembed   Whether to convert multimedia BBCode tag
	 * @param int    $simpleHtml   BBCode::convert method $simple_html parameter value, optional.
	 * @param bool   $forPlaintext BBCode::convert method $for_plaintext parameter value, optional.
	 *
	 * @throws InternalServerErrorException
	 */
	public function testConvert(string $expectedHtml, string $text, $try_oembed = false, int $simpleHtml = 0, bool $forPlaintext = false)
	{
		$actual = BBCode::convert($text, $try_oembed, $simpleHtml, $forPlaintext);

		self::assertEquals($expectedHtml, $actual);
	}

	public function dataBBCodesToMarkdown()
	{
		return [
			'bug-7808-gt' => [
				'expected' => '&gt;`>`',
				'text' => '>[code]>[/code]',
			],
			'bug-7808-lt' => [
				'expected' => '&lt;`<`',
				'text' => '<[code]<[/code]',
			],
			'bug-7808-amp' => [
				'expected' => '&amp;`&`',
				'text' => '&[code]&[/code]',
			],
		];
	}

	/**
	 * Test convert bbcodes to Markdown
	 *
	 * @dataProvider dataBBCodesToMarkdown
	 *
	 * @param string $expected Expected Markdown output
	 * @param string $text     BBCode text
	 * @param bool   $for_diaspora
	 *
	 * @throws InternalServerErrorException
	 */
	public function testToMarkdown(string $expected, string $text, $for_diaspora = false)
	{
		$actual = BBCode::toMarkdown($text, $for_diaspora);

		self::assertEquals($expected, $actual);
	}
}
