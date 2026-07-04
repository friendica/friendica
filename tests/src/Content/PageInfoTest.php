<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content;

use Friendica\Test\DatabaseTestCase;

class PageInfoTest extends DatabaseTestCase
{
	public static function dataGetRelevantUrlFromBody()
	{
		return [
			'end-of-content' => [
				'expected' => 'http://example.com/end-of-content',
				'body'     => 'Content[url]http://example.com/end-of-content[/url]',
			],
			'tag-no-attr' => [
				'expected' => 'http://example.com/tag-no-attr',
				'body'     => '[url]http://example.com/tag-no-attr[/url]',
			],
			'tag-attr' => [
				'expected' => 'http://example.com/tag-attr',
				'body'     => '[url=http://example.com/tag-attr]Example.com[/url]',
			],
			'mention' => [
				'expected' => null,
				'body'     => '@[url=http://example.com/mention]Mention[/url]',
			],
			'mention-exclusive' => [
				'expected' => null,
				'body'     => '@[url=http://example.com/mention-exclusive]Mention Exclusive[/url]',
			],
			'hashtag' => [
				'expected' => null,
				'body'     => '#[url=http://example.com/hashtag]hashtag[/url]',
			],
			'naked-url-unexpected' => [
				'expected' => null,
				'body'     => 'http://example.com/naked-url-unexpected',
			],
			'naked-url-expected' => [
				'expected'        => 'http://example.com/naked-url-expected',
				'body'            => 'http://example.com/naked-url-expected',
				'searchNakedUrls' => true,
			],
			'naked-url-end-of-content-unexpected' => [
				'expected'        => null,
				'body'            => 'Contenthttp://example.com/naked-url-end-of-content-unexpected',
				'searchNakedUrls' => true,
			],
			'naked-url-end-of-content-expected' => [
				'expected'        => 'http://example.com/naked-url-end-of-content-expected',
				'body'            => 'Content http://example.com/naked-url-end-of-content-expected',
				'searchNakedUrls' => true,
			],
			'bug-8781-schemeless-link' => [
				'expected' => null,
				'body'     => '[url]/posts/2576978090fd0138ee4c005056264835[/url]',
			],
		];
	}

	/**
	 *
	 * @param string|null $expected
	 * @param string      $body
	 * @param bool        $searchNakedUrls
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetRelevantUrlFromBody')]
	public function testGetRelevantUrlFromBody($expected, string $body, bool $searchNakedUrls = false): void
	{
		self::assertSame($expected, PageInfoMock::getRelevantUrlFromBody($body, $searchNakedUrls));
	}

	public static function dataStripTrailingUrlFromBody()
	{
		return [
			'naked-url-append' => [
				'expected' => 'content',
				'body'     => 'contenthttps://example.com',
				'url'      => 'https://example.com',
			],
			'naked-url-not-at-the-end' => [
				'expected' => 'https://example.comcontent',
				'body'     => 'https://example.comcontent',
				'url'      => 'https://example.com',
			],
			'bug-8781-labeled-link' => [
				'expected' => 'link label',
				'body'     => '[url=https://example.com]link label[/url]',
				'url'      => 'https://example.com',
			],
			'task-8797-shortened-link-label' => [
				'expected' => 'content',
				'body'     => 'content [url=https://example.com/page]example.com/[/url]',
				'url'      => 'https://example.com/page',
			],
			'task-8797-shortened-link-label-ellipsis' => [
				'expected' => 'content',
				'body'     => 'content [url=https://example.com/page]example.com…[/url]',
				'url'      => 'https://example.com/page',
			],
			'task-8797-shortened-link-label-dots' => [
				'expected' => 'content',
				'body'     => 'content [url=https://example.com/page]example.com...[/url]',
				'url'      => 'https://example.com/page',
			],
		];
	}

	/**
	 *
	 * @param string $expected
	 * @param string $body
	 * @param string $url
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataStripTrailingUrlFromBody')]
	public function testStripTrailingUrlFromBody(string $expected, string $body, string $url): void
	{
		self::assertSame($expected, PageInfoMock::stripTrailingUrlFromBody($body, $url));
	}
}
