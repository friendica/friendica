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

use Friendica\Content\Text\BBCode;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\FixtureTest;

class BBCodeTest extends FixtureTest
{
	protected function setUp(): void
	{
		parent::setUp();
		DI::config()->set('system', 'remove_multiplicated_lines', false);
		DI::config()->set('system', 'no_oembed', false);
		DI::config()->set('system', 'allowed_link_protocols', []);
		DI::config()->set('system', 'url', 'friendica.local');
		DI::config()->set('system', 'no_smilies', false);
		DI::config()->set('system', 'big_emojis', false);
		DI::config()->set('system', 'allowed_oembed', '');

		DI::baseUrl()->save('friendica.local', DI::baseUrl()::SSL_POLICY_FULL, '');

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
				'simpleHtml' => BBCode::DIASPORA,
			],
			'bug-2199-diaspora-no-numeric-size' => [
				'expectedHtml' => 'Test text',
				'text' => '[size=24]Test text[/size]',
				'try_oembed' => false,
				// Triggers the diaspora compatible output
				'simpleHtml' => BBCode::DIASPORA,
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
			'bug-10772-duplicated-links' => [
				'expectedHTML' => 'Jetzt wird mir klar, warum Kapitalisten jedes Mal durchdrehen wenn Marx und das Kapital ins Gespräch kommt. Soziopathen.<br>Karl Marx - Die ursprüngliche Akkumulation<br><a href="https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation" target="_blank" rel="noopener noreferrer">https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation</a><br>#Podcast #Kapitalismus',
				'text' => "Jetzt wird mir klar, warum Kapitalisten jedes Mal durchdrehen wenn Marx und das Kapital ins Gespräch kommt. Soziopathen.
Karl Marx - Die ursprüngliche Akkumulation
[url=https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation]https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation[/url]
#[url=https://horche.demkontinuum.de/search?tag=Podcast]Podcast[/url] #[url=https://horche.demkontinuum.de/search?tag=Kapitalismus]Kapitalismus[/url]
[attachment type='link' url='https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation' title='Ep. 107: Karl Marx #8 - Die urspr&uuml;ngliche Akkumulation' publisher_name='Wohlstand f&uuml;r Alle' preview='https://images.podigee-cdn.net/0x,s6LXshYO7uhG23H431B30t4hxj1bQuzlTsUlze0F_-H8=/https://cdn.podigee.com/uploads/u8126/bd5fe4f4-38b7-4f3f-b269-6a0080144635.jpg']Wie der Kapitalismus funktioniert und inwieweit Menschen darin ausgebeutet werden, haben wir bereits besprochen. Immer wieder verweisen wir auch darauf, dass der Kapitalismus nicht immer schon existierte, sondern historisiert werden muss.[/attachment]",
				'try_oembed' => false,
				'simpleHtml' => BBCode::TWITTER,
			],
			'task-10886-deprecate-class' => [
				'expectedHTML' => '<span class="mastodon emoji"><img src="https://fedi.underscore.world/emoji/custom/custom/heart_nb.png" alt=":heart_nb:" title=":heart_nb:"></span>',
				'text' => '[emoji=https://fedi.underscore.world/emoji/custom/custom/heart_nb.png]:heart_nb:[/emoji]',
			]
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

	public function dataExpandTags()
	{
		return [
			'bug-10692-non-word' => [
				'[url=https://github.com/friendica/friendica/blob/2021.09-rc/src/Util/Logger/StreamLogger.php#L160]https://github.com/friendica/friendica/blob/2021.09-rc/src/Util/Logger/StreamLogger.php#L160[/url]',
				'[url=https://github.com/friendica/friendica/blob/2021.09-rc/src/Util/Logger/StreamLogger.php#L160]https://github.com/friendica/friendica/blob/2021.09-rc/src/Util/Logger/StreamLogger.php#L160[/url]',
			],
			'bug-10692-start-line' => [
				'#[url=https://friendica.local/search?tag=L160]L160[/url]',
				'#L160',
			]
		];
	}

	/**
	 * @dataProvider dataExpandTags
	 *
	 * @param string $expected Expected BBCode output
	 * @param string $text     Input text
	 */
	public function testExpandTags(string $expected, string $text)
	{
		$actual = BBCode::expandTags($text);

		self::assertEquals($expected, $actual);
	}
}
