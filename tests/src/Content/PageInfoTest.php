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

namespace Friendica\Test\src\Content;

use Friendica\Test\DatabaseTest;

class PageInfoTest extends DatabaseTest
{
	public function dataGetRelevantUrlFromBody()
	{
		return [
			'end-of-content' => [
				'expected' => 'http://example.com/end-of-content',
				'body' => 'Content[url]http://example.com/end-of-content[/url]',
			],
			'tag-no-attr' => [
				'expected' => 'http://example.com/tag-no-attr',
				'body' => '[url]http://example.com/tag-no-attr[/url]',
			],
			'tag-attr' => [
				'expected' => 'http://example.com/tag-attr',
				'body' => '[url=http://example.com/tag-attr]Example.com[/url]',
			],
			'mention' => [
				'expected' => null,
				'body' => '@[url=http://example.com/mention]Mention[/url]',
			],
			'mention-exclusive' => [
				'expected' => null,
				'body' => '@[url=http://example.com/mention-exclusive]Mention Exclusive[/url]',
			],
			'hashtag' => [
				'expected' => null,
				'body' => '#[url=http://example.com/hashtag]hashtag[/url]',
			],
			'naked-url-unexpected' => [
				'expected' => null,
				'body' => 'http://example.com/naked-url-unexpected',
			],
			'naked-url-expected' => [
				'expected' => 'http://example.com/naked-url-expected',
				'body' => 'http://example.com/naked-url-expected',
				'searchNakedUrls' => true,
			],
			'naked-url-end-of-content-unexpected' => [
				'expected' => null,
				'body' => 'Contenthttp://example.com/naked-url-end-of-content-unexpected',
				'searchNakedUrls' => true,
			],
			'naked-url-end-of-content-expected' => [
				'expected' => 'http://example.com/naked-url-end-of-content-expected',
				'body' => 'Content http://example.com/naked-url-end-of-content-expected',
				'searchNakedUrls' => true,
			],
			'bug-8781-schemeless-link' => [
				'expected' => null,
				'body' => '[url]/posts/2576978090fd0138ee4c005056264835[/url]',
			],
		];
	}

	/**
	 * @dataProvider dataGetRelevantUrlFromBody
	 *
	 * @param string|null $expected
	 * @param string      $body
	 * @param bool        $searchNakedUrls
	 */
	public function testGetRelevantUrlFromBody($expected, string $body, bool $searchNakedUrls = false)
	{
		self::assertSame($expected, PageInfoMock::getRelevantUrlFromBody($body, $searchNakedUrls));
	}

	public function dataStripTrailingUrlFromBody()
	{
		return [
			'naked-url-append' => [
				'expected' => 'content',
				'body' => 'contenthttps://example.com',
				'url' => 'https://example.com',
			],
			'naked-url-not-at-the-end' => [
				'expected' => 'https://example.comcontent',
				'body' => 'https://example.comcontent',
				'url' => 'https://example.com',
			],
			'bug-8781-labeled-link' => [
				'expected' => 'link label',
				'body' => '[url=https://example.com]link label[/url]',
				'url' => 'https://example.com',
			],
			'task-8797-shortened-link-label' => [
				'expected' => 'content',
				'body' => 'content [url=https://example.com/page]example.com/[/url]',
				'url' => 'https://example.com/page',
			],
			'task-8797-shortened-link-label-ellipsis' => [
				'expected' => 'content',
				'body' => 'content [url=https://example.com/page]example.com…[/url]',
				'url' => 'https://example.com/page',
			],
			'task-8797-shortened-link-label-dots' => [
				'expected' => 'content',
				'body' => 'content [url=https://example.com/page]example.com...[/url]',
				'url' => 'https://example.com/page',
			],
		];
	}

	/**
	 * @dataProvider dataStripTrailingUrlFromBody
	 *
	 * @param string $expected
	 * @param string $body
	 * @param string $url
	 */
	public function testStripTrailingUrlFromBody(string $expected, string $body, string $url)
	{
		self::assertSame($expected, PageInfoMock::stripTrailingUrlFromBody($body, $url));
	}
}
