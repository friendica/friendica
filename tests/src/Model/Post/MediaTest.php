<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model\Post;

use Friendica\Model\Post\Media;
use Friendica\Test\MockedTestCase;

class MediaTest extends MockedTestCase
{
	/**
	 * Test the api_get_attachments() function.
	 *
	 */
	public function testApiGetAttachments(): void
	{
		self::markTestIncomplete('Needs Model\Post\Media refactoring first.');

		// $body = 'body';
		// self::assertEmpty(api_get_attachments($body, 0));
	}

	/**
	 * Test the api_get_attachments() function with an img tag.
	 *
	 */
	public function testApiGetAttachmentsWithImage(): void
	{
		self::markTestIncomplete('Needs Model\Post\Media refactoring first.');

		// $body = '[img]http://via.placeholder.com/1x1.png[/img]';
		// self::assertIsArray(api_get_attachments($body, 0));
	}

	/**
	 * Test the api_get_attachments() function with an img tag and an AndStatus user agent.
	 *
	 */
	public function testApiGetAttachmentsWithImageAndAndStatus(): void
	{
		self::markTestIncomplete('Needs Model\Post\Media refactoring first.');

		// $_SERVER['HTTP_USER_AGENT'] = 'AndStatus';
		// $body                       = '[img]http://via.placeholder.com/1x1.png[/img]';
		// self::assertIsArray(api_get_attachments($body, 0));
	}

	public static function dataRemoveImagesByUrl(): array
	{
		$full    = 'https://example.org/photo/abc-0.png';
		$preview = 'https://example.org/photo/abc-1.png';

		return [
			'linked preview' => ["Text\n[url={$full}][img={$preview}]desc[/img][/url]\n#tag", "Text\n\n#tag"],
			'image with url' => ["[img={$full}]desc[/img] text", ' text'],
			'plain image'    => ["[img]{$full}[/img] text", ' text'],
			'sized image'    => ["[img=10x20]{$full}[/img] text", ' text'],
			'other image'    => ['[img]https://example.org/photo/other-0.png[/img] text', '[img]https://example.org/photo/other-0.png[/img] text'],
			'other link'     => ["[url=https://example.org/page][img]https://example.org/photo/other-0.png[/img][/url]", "[url=https://example.org/page][img]https://example.org/photo/other-0.png[/img][/url]"],
			'link to page'   => ["[url=https://example.org/page][img={$preview}]d[/img][/url] text", ' text'],
			'image in share' => [
				"Comment\n[share author='A' link='https://example.org/p/1']Quoted [url={$full}][img={$preview}]desc[/img][/url] text[/share]",
				"Comment\n[share author='A' link='https://example.org/p/1']Quoted  text[/share]",
			],
			'plain img share' => [
				"Comment\n[share author='A' link='https://example.org/p/1'][img]{$full}[/img] Quoted[/share]",
				"Comment\n[share author='A' link='https://example.org/p/1'] Quoted[/share]",
			],
			'other in share' => [
				"[share author='A' link='https://example.org/p/1'][img]https://example.org/photo/other-0.png[/img][/share]",
				"[share author='A' link='https://example.org/p/1'][img]https://example.org/photo/other-0.png[/img][/share]",
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRemoveImagesByUrl')]
	public function testRemoveImagesByUrl(string $body, string $expected): void
	{
		$urls = ['https://example.org/photo/abc-0.png', 'https://example.org/photo/abc-1.png'];

		self::assertSame($expected, Media::removeImagesByUrl($urls, $body));
	}
}
