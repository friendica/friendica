<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\DI;
use Friendica\Module\Api\Twitter\DirectMessages\All;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Test\ApiTestCase;

class AllTest extends ApiTestCase
{
	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithAll(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/mail/mail.fixture.php', DI::dba());

		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new All($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertGreaterThan(0, count($json));

		foreach ($json as $item) {
			self::assertIsInt($item->id);
			self::assertIsString($item->text);
		}
	}
}
