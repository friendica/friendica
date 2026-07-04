<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Mastodon;

use Friendica\Test\ApiTestCase;

class ConversationsTest extends ApiTestCase
{
	/**
	 * Test the api_conversation_show() function.
	 *
	 */
	public function testApiConversationShow(): void
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_conversation_show('json');
	}

	/**
	 * Test the api_conversation_show() function with an ID.
	 *
	 */
	public function testApiConversationShowWithId(): void
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		/*
		DI::args()->setArgv(['', '', '', 1]);
		$_REQUEST['max_id'] = 10;
		$_REQUEST['page']   = -2;
		$result             = api_conversation_show('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_conversation_show() function with an unallowed user.
	 *
	 */
	public function testApiConversationShowWithUnallowedUser(): void
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_conversation_show('json');
	}
}
