<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content\Text;

use Friendica\Content\Text\BBCode;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\Strings;

class BBCodeTest extends FixtureTestCase
{
	/** @var \HTMLPurifier */
	public $HTMLPurifier;

	protected function setUp(): void
	{
		parent::setUp();
		DI::config()->set('system', 'remove_multiplicated_lines', false);
		DI::config()->set('system', 'allowed_link_protocols', []);
		DI::config()->set('system', 'url', 'https://friendica.local');
		DI::config()->set('system', 'no_smilies', false);
		DI::config()->set('system', 'big_emojis', false);
		DI::config()->set('system', 'allowed_oembed', '');

		$config = \HTMLPurifier_HTML5Config::createDefault();
		$config->set('HTML.Doctype', 'HTML5');
		$config->set('Attr.AllowedRel', [
			'noreferrer' => true,
			'noopener'   => true,
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
				'data'       => 'https://de.wikipedia.org/wiki/Juha_Sipilä',
				'assertHTML' => true,
			],
			'bug-2487-2' => [
				'data'       => 'https://de.wikipedia.org/wiki/Dnepr_(Motorradmarke)',
				'assertHTML' => true,
			],
			'bug-2487-3' => [
				'data'       => 'https://friendica.wäckerlin.ch/friendica',
				'assertHTML' => true,
			],
			'bug-2487-4' => [
				'data'       => 'https://mastodon.social/@morevnaproject',
				'assertHTML' => true,
			],
			/** @see https://github.com/friendica/friendica/issues/5795 */
			'bug-5795' => [
				'data'       => 'https://social.nasqueron.org/@liw/100798039015010628',
				'assertHTML' => true,
			],
			/** @see https://github.com/friendica/friendica/issues/6095 */
			'bug-6095' => [
				'data'       => 'https://en.wikipedia.org/wiki/Solid_(web_decentralization_project)',
				'assertHTML' => true,
			],
			'no-protocol' => [
				'data'       => 'example.com/path',
				'assertHTML' => false
			],
			'wrong-protocol' => [
				'data'       => 'ftp://example.com',
				'assertHTML' => false
			],
			'wrong-domain-without-path' => [
				'data'       => 'http://example',
				'assertHTML' => false
			],
			'wrong-domain-with-path' => [
				'data'       => 'http://example/path',
				'assertHTML' => false
			],
			'bug-6857-domain-start' => [
				'data'       => "http://\nexample.com",
				'assertHTML' => false
			],
			'bug-6857-domain-end' => [
				'data'       => "http://example\n.com",
				'assertHTML' => false
			],
			'bug-6857-tld' => [
				'data'       => "http://example.\ncom",
				'assertHTML' => false
			],
			'bug-6857-end' => [
				'data'       => "http://example.com\ntest",
				'assertHTML' => false
			],
			'bug-6901' => [
				'data'       => "http://example.com<ul>",
				'assertHTML' => false
			],
			'bug-7150' => [
				'data'       => html_entity_decode('http://example.com&nbsp;', ENT_QUOTES, 'UTF-8'),
				'assertHTML' => false
			],
			'bug-7271-query-string-brackets' => [
				'data'       => 'https://example.com/search?q=square+brackets+[url]',
				'assertHTML' => true
			],
			'bug-7271-path-brackets' => [
				'data'       => 'http://example.com/path/to/file[3].html',
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
		$assert = $this->HTMLPurifier->purify('<a href="' . $data . '" target="_blank" rel="noopener noreferrer">' . Strings::getStyledURL($data) . '</a>');
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
				'expectedHtml' => '<ol><li> <a href="http://example.com/" target="_blank" rel="noopener noreferrer">example.com/</a></li></ol>',
				'text'         => '[ol][li] http://example.com/[/ol]',
			],
			'bug-7271-condensed-nospace' => [
				'expectedHtml' => '<ol><li><a href="http://example.com/" target="_blank" rel="noopener noreferrer">example.com/</a></li></ol>',
				'text'         => '[ol][li]http://example.com/[/ol]',
			],
			'bug-7271-indented-space' => [
				'expectedHtml' => '<ul><li> <a href="http://example.com/" target="_blank" rel="noopener noreferrer">example.com/</a></li></ul>',
				'text'         => '[ul]
[li] http://example.com/
[/ul]',
			],
			'bug-7271-indented-nospace' => [
				'expectedHtml' => '<ul><li><a href="http://example.com/" target="_blank" rel="noopener noreferrer">example.com/</a></li></ul>',
				'text'         => '[ul]
[li]http://example.com/
[/ul]',
			],
			'bug-2199-named-size' => [
				'expectedHtml' => '<span style="font-size:xx-large;line-height:normal;">Test text</span>',
				'text'         => '[size=xx-large]Test text[/size]',
			],
			'bug-2199-numeric-size' => [
				'expectedHtml' => '<span style="font-size:24px;line-height:normal;">Test text</span>',
				'text'         => '[size=24]Test text[/size]',
			],
			'bug-2199-diaspora-no-named-size' => [
				'expectedHtml' => 'Test text',
				'text'         => '[size=xx-large]Test text[/size]',
				'try_oembed'   => false,
				// Triggers the diaspora compatible output
				'simpleHtml' => BBCode::DIASPORA,
			],
			'bug-2199-diaspora-no-numeric-size' => [
				'expectedHtml' => 'Test text',
				'text'         => '[size=24]Test text[/size]',
				'try_oembed'   => false,
				// Triggers the diaspora compatible output
				'simpleHtml' => BBCode::DIASPORA,
			],
			'bug-7665-audio-tag' => [
				'expectedHtml' => '<audio src="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3" controls><a href="http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3">http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3</a></audio>',
				'text'         => '[audio]http://www.cendrones.fr/colloque2017/jonathanbocquet.mp3[/audio]',
				'try_oembed'   => true,
			],
			'bug-7808-code-lt' => [
				'expectedHtml' => '<code>&lt;</code>',
				'text'         => '[code]<[/code]',
			],
			'bug-7808-code-gt' => [
				'expectedHtml' => '<code>&gt;</code>',
				'text'         => '[code]>[/code]',
			],
			'bug-7808-code-amp' => [
				'expectedHtml' => '<code>&amp;</code>',
				'text'         => '[code]&[/code]',
			],
			'task-8800-pre-spaces-notag' => [
				'expectedHtml' => '[test] Space',
				'text'         => '[test] Space',
			],
			'task-8800-pre-spaces' => [
				'expectedHtml' => '    Spaces',
				'text'         => '[pre]    Spaces[/pre]',
			],
			'bug-9611-purify-xss-nobb' => [
				'expectedHTML' => '<span>dare to move your mouse here</span>',
				'text'         => '[nobb]<span onmouseover="alert(0)">dare to move your mouse here</span>[/nobb]'
			],
			'bug-9611-purify-xss-noparse' => [
				'expectedHTML' => '<span>dare to move your mouse here</span>',
				'text'         => '[noparse]<span onmouseover="alert(0)">dare to move your mouse here</span>[/noparse]'
			],
			'bug-9611-purify-xss-attributes' => [
				'expectedHTML' => '<span>dare to move your mouse here</span>',
				'text'         => '[color="onmouseover=alert(0) style="]dare to move your mouse here[/color]'
			],
			'bug-9611-purify-attributes-correct' => [
				'expectedHTML' => '<span style="color:#FFFFFF;">dare to move your mouse here</span>',
				'text'         => '[color=FFFFFF]dare to move your mouse here[/color]'
			],
			'bug-9639-span-classes' => [
				'expectedHTML' => '<span class="arbitrary classes">Test</span>',
				'text'         => '[class=arbitrary classes]Test[/class]',
			],
			'bug-10772-duplicated-links' => [
				'expectedHTML' => 'Jetzt wird mir klar, warum Kapitalisten jedes Mal durchdrehen wenn Marx und das Kapital ins Gespräch kommt. Soziopathen.<br>Karl Marx - Die ursprüngliche Akkumulation<br><a href="https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation" target="_blank" rel="noopener noreferrer">https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation</a><br>#Podcast #Kapitalismus',
				'text'         => "Jetzt wird mir klar, warum Kapitalisten jedes Mal durchdrehen wenn Marx und das Kapital ins Gespräch kommt. Soziopathen.
Karl Marx - Die ursprüngliche Akkumulation
[url=https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation]https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation[/url]
#[url=https://horche.demkontinuum.de/search?tag=Podcast]Podcast[/url] #[url=https://horche.demkontinuum.de/search?tag=Kapitalismus]Kapitalismus[/url]
[attachment type='link' url='https://wohlstandfueralle.podigee.io/107-urspruengliche-akkumulation' title='Ep. 107: Karl Marx #8 - Die urspr&uuml;ngliche Akkumulation' publisher_name='Wohlstand f&uuml;r Alle' preview='https://images.podigee-cdn.net/0x,s6LXshYO7uhG23H431B30t4hxj1bQuzlTsUlze0F_-H8=/https://cdn.podigee.com/uploads/u8126/bd5fe4f4-38b7-4f3f-b269-6a0080144635.jpg']Wie der Kapitalismus funktioniert und inwieweit Menschen darin ausgebeutet werden, haben wir bereits besprochen. Immer wieder verweisen wir auch darauf, dass der Kapitalismus nicht immer schon existierte, sondern historisiert werden muss.[/attachment]",
				'try_oembed' => false,
				'simpleHtml' => BBCode::TWITTER,
			],
			'task-10886-deprecate-class' => [
				'expectedHTML' => '<span class="mastodon emoji"><img src="https://fedi.underscore.world/emoji/custom/custom/heart_nb.png" alt=":heart_nb:" title=":heart_nb:"></span>',
				'text'         => '[emoji=https://fedi.underscore.world/emoji/custom/custom/heart_nb.png]:heart_nb:[/emoji]',
			],
			'task-12900-multiple-paragraphs' => [
				'expectedHTML' => '<h3>Header</h3><ul><li>One</li><li>Two</li></ul><p>This is a paragraph<br>with a line feed.</p><p>Second Chapter</p>',
				'text'         => "[h4]Header[/h4][ul][li]One[li]Two[/ul]\n\nThis is a paragraph\nwith a line feed.\n\nSecond Chapter",
			],
			'task-12900-header-with-paragraphs' => [
				'expectedHTML' => '<h3>Header</h3><p>Some Chapter</p>',
				'text'         => '[h4]Header[/h4]Some Chapter',
			],
			'bug-12842-ul-newlines' => [
				'expectedHTML' => '<p>This is:</p><ul><li>some</li><li>amazing</li><li>list</li></ul>',
				'text'         => "This is:\r\n[ul]\r\n[li]some\r\n[li]amazing\r\n[li]list\r\n[/ul]",
			],
			'bug-12842-ol-newlines' => [
				'expectedHTML' => '<p>This is:</p><ol><li>some</li><li>amazing</li><li>list</li></ol>',
				'text'         => "This is:\r\n[ol]\r\n[li]some\r\n[li]amazing\r\n[li]list\r\n[/ol]",
			],
			'task-12917-tabs-between-linebreaks' => [
				'expectedHTML' => '<p>Paragraph</p><p>New Paragraph</p>',
				'text'         => "Paragraph\n\t\nNew Paragraph",
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
	public function testConvert(string $expectedHtml, string $text, bool $try_oembed = true, int $simpleHtml = BBCode::INTERNAL, bool $forPlaintext = false)
	{
		// This assumes system.remove_multiplicated_lines = false
		$actual = BBCode::convert($text, $try_oembed, $simpleHtml, $forPlaintext);

		self::assertEquals($expectedHtml, $actual);
	}

	public function dataBBCodesToMarkdown()
	{
		return [
			'bug-7808-gt' => [
				'expected' => '&gt;`>`',
				'text'     => '>[code]>[/code]',
			],
			'bug-7808-lt' => [
				'expected' => '&lt;`<`',
				'text'     => '<[code]<[/code]',
			],
			'bug-7808-amp' => [
				'expected' => '&amp;`&`',
				'text'     => '&[code]&[/code]',
			],
			'bug-12701-quotes' => [
				'expected' => '[![abc"fgh](https://domain.tld/photo/86912721086415cdc8e0a03226831581-1.png)](https://domain.tld/photos/user/image/86912721086415cdc8e0a03226831581)',
				'text'     => '[url=https://domain.tld/photos/user/image/86912721086415cdc8e0a03226831581][img=https://domain.tld/photo/86912721086415cdc8e0a03226831581-1.png]abc"fgh[/img][/url]'
			],
			'bug-12701-no-quotes' => [
				'expected' => '[![abcfgh](https://domain.tld/photo/86912721086415cdc8e0a03226831581-1.png "abcfgh")](https://domain.tld/photos/user/image/86912721086415cdc8e0a03226831581)',
				'text'     => '[url=https://domain.tld/photos/user/image/86912721086415cdc8e0a03226831581][img=https://domain.tld/photo/86912721086415cdc8e0a03226831581-1.png]abcfgh[/img][/url]'
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
	public function testToMarkdown(string $expected, string $text, $for_diaspora = true)
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

	public function dataGetAbstract(): array
	{
		return [
			'no-abstract' => [
				'expected' => '',
				'text'     => 'Venture the only home we\'ve ever known laws of physics tendrils of gossamer clouds a still more glorious dawn awaits Sea of Tranquility. With pretty stories for which there\'s little good evidence the ash of stellar alchemy corpus callosum preserve and cherish that pale blue dot descended from astronomers preserve and cherish that pale blue dot. A mote of dust suspended in a sunbeam paroxysm of global death two ghostly white figures in coveralls and helmets are softly dancing descended from astronomers star stuff harvesting star light gathered by gravity and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'addon'    => '',
			],
			'no-abstract-addon' => [
				'expected' => '',
				'text'     => 'Tingling of the spine tendrils of gossamer clouds Flatland trillion rich in heavy atoms of brilliant syntheses. Extraordinary claims require extraordinary evidence a very small stage in a vast cosmic arena made in the interiors of collapsing stars kindling the energy hidden in matter vastness is bearable only through love kindling the energy hidden in matter? Dispassionate extraterrestrial observer preserve and cherish that pale blue dot vastness is bearable only through love emerged into consciousness encyclopaedia galactica a still more glorious dawn awaits and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'addon'    => 'dfrn',
			],
			'abstract' => [
				'expected' => 'Abstract at the beginning of the text',
				'text'     => '[abstract]Abstract at the beginning of the text[/abstract]A very small stage in a vast cosmic arena the ash of stellar alchemy rich in heavy atoms a still more glorious dawn awaits are creatures of the cosmos Orion\'s sword. Brain is the seed of intelligence dream of the mind\'s eye inconspicuous motes of rock and gas extraordinary claims require extraordinary evidence vastness is bearable only through love quasar? Made in the interiors of collapsing stars the carbon in our apple pies cosmic ocean citizens of distant epochs paroxysm of global death dispassionate extraterrestrial observer and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'addon'    => '',
			],
			'abstract-addon-not-present' => [
				'expected' => 'Abstract at the beginning of the text',
				'text'     => '[abstract]Abstract at the beginning of the text[/abstract]With pretty stories for which there\'s little good evidence rogue not a sunrise but a galaxyrise tingling of the spine birth cosmic fugue. Cosmos hundreds of thousands Apollonius of Perga network of wormholes rich in mystery globular star cluster. Another world vastness is bearable only through love encyclopaedia galactica something incredible is waiting to be known invent the universe hearts of the stars. Extraordinary claims require extraordinary evidence the sky calls to us the only home we\'ve ever known the sky calls to us the sky calls to us extraordinary claims require extraordinary evidence and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'addon'    => '',
			],
			'abstract-addon-present' => [
				'expected' => 'Abstract DFRN in the middle of the text',
				'text'     => '[abstract]Abstract at the beginning of the text[/abstract][abstract=dfrn]Abstract DFRN in the middle of the text[/abstract]Kindling the energy hidden in matter hydrogen atoms at the edge of forever vanquish the impossible ship of the imagination take root and flourish. Tingling of the spine white dwarf as a patch of light the sky calls to us Drake Equation citizens of distant epochs. Concept of the number one dispassionate extraterrestrial observer citizens of distant epochs descended from astronomers extraordinary claims require extraordinary evidence something incredible is waiting to be known and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'addon'    => 'dfrn',
			],
			'abstract-multiple-addon-present' => [
				'expected' => 'Abstract DFRN at the end of the text',
				'text'     => '[abstract]Abstract at the beginning of the text[/abstract][abstract=ap]Abstract AP in the middle of the text[/abstract]Cambrian explosion rich in heavy atoms take root and flourish radio telescope light years cosmic fugue. Dispassionate extraterrestrial observer white dwarf the sky calls to us another world courage of our questions two ghostly white figures in coveralls and helmets are softly dancing. Extraordinary claims require extraordinary evidence concept of the number one not a sunrise but a galaxyrise are creatures of the cosmos two ghostly white figures in coveralls and helmets are softly dancing white dwarf and billions upon billions upon billions upon billions upon billions upon billions upon billions.[abstract=dfrn]Abstract DFRN at the end of the text[/abstract]',
				'addon'    => 'dfrn',
			],
			'bug-11445-code-abstract' => [
				'expected' => '',
				'text'     => '[code][abstract]This should not be converted[/abstract][/code]',
				'addon'    => '',
			],
			'bug-11445-noparse-abstract' => [
				'expected' => '',
				'text'     => '[noparse][abstract]This should not be converted[/abstract][/noparse]',
				'addon'    => '',
			],
			'bug-11445-nobb-abstract' => [
				'expected' => '',
				'text'     => '[nobb][abstract]This should not be converted[/abstract][/nobb]',
				'addon'    => '',
			],
			'bug-11445-pre-abstract' => [
				'expected' => '',
				'text'     => '[pre][abstract]This should not be converted[/abstract][/pre]',
				'addon'    => '',
			],
		];
	}

	/**
	 * @dataProvider dataGetAbstract
	 *
	 * @param string $expected Expected abstract text
	 * @param string $text     Input text
	 * @param string $addon    Optional addon we're searching the abstract for
	 */
	public function testGetAbstract(string $expected, string $text, string $addon)
	{
		$actual = BBCode::getAbstract($text, $addon);

		self::assertEquals($expected, $actual);
	}


	public function dataStripAbstract(): array
	{
		return [
			'no-abstract' => [
				'expected' => 'Venture the only home we\'ve ever known laws of physics tendrils of gossamer clouds a still more glorious dawn awaits Sea of Tranquility. With pretty stories for which there\'s little good evidence the ash of stellar alchemy corpus callosum preserve and cherish that pale blue dot descended from astronomers preserve and cherish that pale blue dot. A mote of dust suspended in a sunbeam paroxysm of global death two ghostly white figures in coveralls and helmets are softly dancing descended from astronomers star stuff harvesting star light gathered by gravity and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'text'     => 'Venture the only home we\'ve ever known laws of physics tendrils of gossamer clouds a still more glorious dawn awaits Sea of Tranquility. With pretty stories for which there\'s little good evidence the ash of stellar alchemy corpus callosum preserve and cherish that pale blue dot descended from astronomers preserve and cherish that pale blue dot. A mote of dust suspended in a sunbeam paroxysm of global death two ghostly white figures in coveralls and helmets are softly dancing descended from astronomers star stuff harvesting star light gathered by gravity and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
			],
			'abstract' => [
				'expected' => ' A very small stage in a vast cosmic arena the ash of stellar alchemy rich in heavy atoms a still more glorious dawn awaits are creatures of the cosmos Orion\'s sword. Brain is the seed of intelligence dream of the mind\'s eye inconspicuous motes of rock and gas extraordinary claims require extraordinary evidence vastness is bearable only through love quasar? Made in the interiors of collapsing stars the carbon in our apple pies cosmic ocean citizens of distant epochs paroxysm of global death dispassionate extraterrestrial observer and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'text'     => '[abstract]Abstract at the beginning of the text[/abstract]A very small stage in a vast cosmic arena the ash of stellar alchemy rich in heavy atoms a still more glorious dawn awaits are creatures of the cosmos Orion\'s sword. Brain is the seed of intelligence dream of the mind\'s eye inconspicuous motes of rock and gas extraordinary claims require extraordinary evidence vastness is bearable only through love quasar? Made in the interiors of collapsing stars the carbon in our apple pies cosmic ocean citizens of distant epochs paroxysm of global death dispassionate extraterrestrial observer and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
			],
			'abstract-addon' => [
				'expected' => ' Kindling the energy hidden in matter hydrogen atoms at the edge of forever vanquish the impossible ship of the imagination take root and flourish. Tingling of the spine white dwarf as a patch of light the sky calls to us Drake Equation citizens of distant epochs. Concept of the number one dispassionate extraterrestrial observer citizens of distant epochs descended from astronomers extraordinary claims require extraordinary evidence something incredible is waiting to be known and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
				'text'     => '[abstract]Abstract at the beginning of the text[/abstract][abstract=dfrn]Abstract DFRN in the middle of the text[/abstract]Kindling the energy hidden in matter hydrogen atoms at the edge of forever vanquish the impossible ship of the imagination take root and flourish. Tingling of the spine white dwarf as a patch of light the sky calls to us Drake Equation citizens of distant epochs. Concept of the number one dispassionate extraterrestrial observer citizens of distant epochs descended from astronomers extraordinary claims require extraordinary evidence something incredible is waiting to be known and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
			],
			'abstract-multiple-addon-present' => [
				'expected' => ' Cambrian explosion rich in heavy atoms take root and flourish radio telescope light years cosmic fugue. Dispassionate extraterrestrial observer white dwarf the sky calls to us another world courage of our questions two ghostly white figures in coveralls and helmets are softly dancing. Extraordinary claims require extraordinary evidence concept of the number one not a sunrise but a galaxyrise are creatures of the cosmos two ghostly white figures in coveralls and helmets are softly dancing white dwarf and billions upon billions upon billions upon billions upon billions upon billions upon billions. ',
				'text'     => '[abstract]Abstract at the beginning of the text[/abstract][abstract=ap]Abstract AP in the middle of the text[/abstract]Cambrian explosion rich in heavy atoms take root and flourish radio telescope light years cosmic fugue. Dispassionate extraterrestrial observer white dwarf the sky calls to us another world courage of our questions two ghostly white figures in coveralls and helmets are softly dancing. Extraordinary claims require extraordinary evidence concept of the number one not a sunrise but a galaxyrise are creatures of the cosmos two ghostly white figures in coveralls and helmets are softly dancing white dwarf and billions upon billions upon billions upon billions upon billions upon billions upon billions.[abstract=dfrn]Abstract DFRN at the end of the text[/abstract]',
			],
			'bug-11445-code-abstract' => [
				'expected' => '[code][abstract]This should not be converted[/abstract][/code]',
				'text'     => '[code][abstract]This should not be converted[/abstract][/code]',
			],
			'bug-11445-noparse-abstract' => [
				'expected' => '[noparse][abstract]This should not be converted[/abstract][/noparse]',
				'text'     => '[noparse][abstract]This should not be converted[/abstract][/noparse]',
			],
			'bug-11445-nobb-abstract' => [
				'expected' => '[nobb][abstract]This should not be converted[/abstract][/nobb]',
				'text'     => '[nobb][abstract]This should not be converted[/abstract][/nobb]',
			],
			'bug-11445-pre-abstract' => [
				'expected' => '[pre][abstract]This should not be converted[/abstract][/pre]',
				'text'     => '[pre][abstract]This should not be converted[/abstract][/pre]',
			],
		];
	}

	/**
	 * @dataProvider dataStripAbstract
	 *
	 * @param string $expected Expected text without abstracts
	 * @param string $text     Input text
	 */
	public function testStripAbstract(string $expected, string $text)
	{
		$actual = BBCode::stripAbstract($text);

		self::assertEquals($expected, $actual);
	}

	public function dataFetchShareAttributes(): array
	{
		return [
			'no-tag' => [
				'expected' => [],
				'text'     => 'Venture the only home we\'ve ever known laws of physics tendrils of gossamer clouds a still more glorious dawn awaits Sea of Tranquility. With pretty stories for which there\'s little good evidence the ash of stellar alchemy corpus callosum preserve and cherish that pale blue dot descended from astronomers preserve and cherish that pale blue dot. A mote of dust suspended in a sunbeam paroxysm of global death two ghostly white figures in coveralls and helmets are softly dancing descended from astronomers star stuff harvesting star light gathered by gravity and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
			],
			'just-open' => [
				'expected' => [],
				'text'     => '[share]',
			],
			'empty-tag' => [
				'expected' => [
					'author'     => '',
					'profile'    => '',
					'avatar'     => '',
					'link'       => '',
					'posted'     => '',
					'guid'       => '',
					'message_id' => '',
					'comment'    => '',
					'shared'     => '',
				],
				'text' => '[share][/share]',
			],
			'comment-shared' => [
				'expected' => [
					'author'     => '',
					'profile'    => '',
					'avatar'     => '',
					'link'       => '',
					'posted'     => '',
					'guid'       => '',
					'message_id' => 'https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243',
					'comment'    => 'comment',
					'shared'     => '',
				],
				'text' => ' comment
				[share]https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243[/share]',
			],
			'all-attributes' => [
				'expected' => [
					'author'     => 'Hypolite Petovan',
					'profile'    => 'https://friendica.mrpetovan.com/profile/hypolite',
					'avatar'     => 'https://friendica.mrpetovan.com/photo/20682437145daa4e85f019a278584494-5.png',
					'link'       => 'https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243',
					'posted'     => '2022-06-16 12:34:10',
					'guid'       => '735a2029-1062-ab23-42e4-f9c631220243',
					'message_id' => 'https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243',
					'comment'    => '',
					'shared'     => 'George Lucas: I made a science-fiction universe with a straightforward anti-authoritarianism plot where even the libertarian joins the rebellion.
Disney: So a morally grey “choose your side” story, right?
Lucas: For the right price, yes.',
				],
				'text' => "[share
					author='Hypolite Petovan'
					profile='https://friendica.mrpetovan.com/profile/hypolite'
					avatar='https://friendica.mrpetovan.com/photo/20682437145daa4e85f019a278584494-5.png'
					link='https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243'
					posted='2022-06-16 12:34:10'
					guid='735a2029-1062-ab23-42e4-f9c631220243'
					message_id='https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243'
				]George Lucas: I made a science-fiction universe with a straightforward anti-authoritarianism plot where even the libertarian joins the rebellion.
Disney: So a morally grey “choose your side” story, right?
Lucas: For the right price, yes.[/share]",
			],
			'optional-attributes' => [
				'expected' => [
					'author'     => 'Hypolite Petovan',
					'profile'    => 'https://friendica.mrpetovan.com/profile/hypolite',
					'avatar'     => 'https://friendica.mrpetovan.com/photo/20682437145daa4e85f019a278584494-5.png',
					'link'       => 'https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243',
					'posted'     => '2022-06-16 12:34:10',
					'guid'       => '',
					'message_id' => 'https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243',
					'comment'    => '',
					'shared'     => 'George Lucas: I made a science-fiction universe with a straightforward anti-authoritarianism plot where even the libertarian joins the rebellion.
Disney: So a morally grey “choose your side” story, right?
Lucas: For the right price, yes.',
				],
				'text' => "[share
					author='Hypolite Petovan'
					profile='https://friendica.mrpetovan.com/profile/hypolite'
					avatar='https://friendica.mrpetovan.com/photo/20682437145daa4e85f019a278584494-5.png'
					link='https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243'
					posted='2022-06-16 12:34:10'
					message_id='https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243'
				]George Lucas: I made a science-fiction universe with a straightforward anti-authoritarianism plot where even the libertarian joins the rebellion.
Disney: So a morally grey “choose your side” story, right?
Lucas: For the right price, yes.[/share]",
			],
			'double-quotes' => [
				'expected' => [
					'author'     => 'Hypolite Petovan',
					'profile'    => 'https://friendica.mrpetovan.com/profile/hypolite',
					'avatar'     => 'https://friendica.mrpetovan.com/photo/20682437145daa4e85f019a278584494-5.png',
					'link'       => 'https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243',
					'posted'     => '2022-06-16 12:34:10',
					'guid'       => '',
					'message_id' => 'https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243',
					'comment'    => '',
					'shared'     => 'George Lucas: I made a science-fiction universe with a straightforward anti-authoritarianism plot where even the libertarian joins the rebellion.
Disney: So a morally grey “choose your side” story, right?
Lucas: For the right price, yes.',
				],
				'text' => '[share
					author="Hypolite Petovan"
					profile="https://friendica.mrpetovan.com/profile/hypolite"
					avatar="https://friendica.mrpetovan.com/photo/20682437145daa4e85f019a278584494-5.png"
					link="https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243"
					message_id="https://friendica.mrpetovan.com/display/735a2029-1062-ab23-42e4-f9c631220243"
					posted="2022-06-16 12:34:10"
				]George Lucas: I made a science-fiction universe with a straightforward anti-authoritarianism plot where even the libertarian joins the rebellion.
Disney: So a morally grey “choose your side” story, right?
Lucas: For the right price, yes.[/share]',
			],
		];
	}

	/**
	 * @dataProvider dataFetchShareAttributes
	 *
	 * @param array $expected Expected attribute array
	 * @param string $text    Input text
	 */
	public function testFetchShareAttributes(array $expected, string $text)
	{
		$actual = BBCode::fetchShareAttributes($text);

		self::assertEquals($expected, $actual);
	}
}
