<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\Conversation;
use Friendica\Test\ApiTestCase;

class ConversationTest extends ApiTestCase
{
	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithConversation(): void
	{
		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new Conversation($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json'])) // @phpstan-ignore method.deprecated
			->run($this->httpExceptionMock, [
				'friendica_verbose' => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('no mails available', $json->message);
	}

	public function testHandleRequestDirectMessagesConversationReturnsErrorMessage(): void
	{
		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$module = new Conversation($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn(['friendica_verbose' => true]);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('no mails available', $json->message);
	}
}
