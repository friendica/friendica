<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\Sent;
use Friendica\Test\ApiTestCase;

class SentTest extends ApiTestCase
{
	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithVerbose(): void
	{
		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new Sent($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock, [
				'friendica_verbose' => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('no mails available', $json->message);
	}

	/**
	 * Test the api_direct_messages_box() function with a RSS result.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithRss(): void
	{
		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new Sent($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'rss']))
			->run($this->httpExceptionMock);

		self::assertXml((string) $response->getBody(), 'direct-messages');
	}

	/**
	 * Test the api_direct_messages_box() function without an authenticated user.
	 *
	 */
	public function testApiDirectMessagesBoxWithUnallowedUser(): void
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		//$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		//BasicAuth::setCurrentUserID();
		//api_direct_messages_box('json', 'sentbox', 'false');
	}
}
