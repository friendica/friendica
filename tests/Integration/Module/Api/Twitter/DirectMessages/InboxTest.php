<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\DirectMessages;

use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\Inbox;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class InboxTest extends ApiTestCase
{
	public function testApiDirectMessagesBoxWithInbox(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/mail/mail.fixture.php', DI::dba());

		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$module = new Inbox($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/direct_messages/inbox'))
			->withQueryParams(['format' => 'json']);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertGreaterThan(0, count($json));

		foreach ($json as $item) {
			self::assertIsInt($item->id);
			self::assertIsString($item->text);
		}
	}
}
