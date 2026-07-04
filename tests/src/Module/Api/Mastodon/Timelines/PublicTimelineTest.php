<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Mastodon\Timelines;

use Friendica\Test\ApiTestCase;

class PublicTimelineTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_public_timeline() function.
	 *
	 */
	public function testApiStatusesPublicTimeline(): void
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['conversation_id'] = 1;
		$result                      = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with the exclude_replies parameter.
	 *
	 */
	public function testApiStatusesPublicTimelineWithExcludeReplies(): void
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['exclude_replies'] = true;
		$result                      = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with a negative page parameter.
	 *
	 */
	public function testApiStatusesPublicTimelineWithNegativePage(): void
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['page'] = -2;
		$result           = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with an unallowed user.
	 *
	 */
	public function testApiStatusesPublicTimelineWithUnallowedUser(): void
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_public_timeline('json');
	}

	/**
	 * Test the api_statuses_public_timeline() function with an RSS result.
	 *
	 */
	public function testApiStatusesPublicTimelineWithRss(): void
	{
		self::markTestIncomplete('Needs PublicTimeline to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_statuses_public_timeline('rss');
		// self::assertXml($result, 'statuses');
	}
}
