<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Friendica\DirectMessages;

use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Friendica\DirectMessages\Search;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Log\NullLogger;

final class SearchTest extends ApiTestCase
{
	public function testEmpty(): void
	{
		$directMessage = new DirectMessage(new NullLogger(), DI::dba(), DI::twitterUser());

		$module = new Search($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = new ServerRequest('GET', 'https://friendica.local/api/friendica/direct_messages_search');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		$assert          = new \stdClass();
		$assert->result  = 'error';
		$assert->message = 'searchstring not specified';

		self::assertEquals($assert, $json);
	}

	public function testMail(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/mail/mail.fixture.php', DI::dba());

		$directMessage = new DirectMessage(new NullLogger(), DI::dba(), DI::twitterUser());

		$module = new Search($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/friendica/direct_messages_search'))
			->withQueryParams(['searchstring' => 'item_body']);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertTrue($json->success);

		foreach ($json->search_results as $searchResult) {
			self::assertIsObject($searchResult->sender);
			self::assertIsInt($searchResult->id);
			self::assertIsInt($searchResult->sender_id);
			self::assertIsObject($searchResult->recipient);
		}
	}

	public function testNothingFound(): void
	{
		$directMessage = new DirectMessage(new NullLogger(), DI::dba(), DI::twitterUser());

		$module = new Search($directMessage, DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/friendica/direct_messages_search'))
			->withQueryParams(['searchstring' => 'test']);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		$assert                 = new \stdClass();
		$assert->success        = false;
		$assert->search_results = 'nothing found';

		self::assertEquals($assert, $json);
	}
}
