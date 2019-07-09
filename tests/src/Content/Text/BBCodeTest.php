<?php

namespace Friendica\Test\src\Content\Text;

use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n\L10n;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\VFSTrait;

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

		$l10nMock = \Mockery::mock(L10n::class);
		$l10nMock->shouldReceive('t')->withAnyArgs()->andReturnUsing(function ($args) { return $args; });
		\Friendica\Core\L10n::init($l10nMock);

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
	 * @dataProvider dataLinks
	 *
	 * @param string $data The data to text
	 * @param bool $assertHTML True, if the link is a HTML link (<a href...>...</a>)
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testAutoLinking($data, $assertHTML)
	{
		$output = BBCode::convert($data);
		$assert = '<a href="' . $data . '" target="_blank">' . $data . '</a>';
		if ($assertHTML) {
			$this->assertEquals($assert, $output);
		} else {
			$this->assertNotEquals($assert, $output);
		}
	}

	public function dataBBCodes()
	{
		return [
			'bug-7271-condensed-space' => [
				'expectedHtml' => '<ul class="listdecimal" style="list-style-type: decimal;"><li> <a href="http://example.com/" target="_blank">http://example.com/</a></li></ul>',
				'text' => '[ol][*] http://example.com/[/ol]',
			],
			'bug-7271-condensed-nospace' => [
				'expectedHtml' => '<ul class="listdecimal" style="list-style-type: decimal;"><li><a href="http://example.com/" target="_blank">http://example.com/</a></li></ul>',
				'text' => '[ol][*]http://example.com/[/ol]',
			],
			'bug-7271-indented-space' => [
				'expectedHtml' => '<ul class="listbullet" style="list-style-type: circle;"><li> <a href="http://example.com/" target="_blank">http://example.com/</a></li></ul>',
				'text' => '[ul]
[*] http://example.com/
[/ul]',
			],
			'bug-7271-indented-nospace' => [
				'expectedHtml' => '<ul class="listbullet" style="list-style-type: circle;"><li><a href="http://example.com/" target="_blank">http://example.com/</a></li></ul>',
				'text' => '[ul]
[*]http://example.com/
[/ul]',
			],
		];
	}

	/**
	 * Test convert bbcodes to HTML
	 * @dataProvider dataBBCodes
	 *
	 * @param string $expectedHtml Expected HTML output
	 * @param string $text         BBCode text
	 * @param int    $simpleHtml   BBCode::convert method $simple_html parameter value, optional.
	 * @param bool   $forPlaintext BBCode::convert method $for_plaintext parameter value, optional.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testConvert($expectedHtml, $text, $simpleHtml = 0, $forPlaintext = false)
	{
		$actual = BBCode::convert($text, false, $simpleHtml, $forPlaintext);

		$this->assertEquals($expectedHtml, $actual);
	}
}
