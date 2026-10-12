<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model\Post;

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
}
