<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Account;

use Friendica\DI;
use Friendica\Module\Api\Twitter\Account\RateLimitStatus;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class RateLimitStatusTest extends ApiTestCase
{
	public function testWithJson(): void
	{
		$module = new RateLimitStatus(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);

		$request = new ServerRequest('GET', 'https://friendica.local/api/account/rate_limit_status');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$result = $this->toJson($response);

		self::assertEquals(150, $result->remaining_hits);
		self::assertEquals(150, $result->hourly_limit);
		self::assertIsInt($result->reset_time_in_seconds);
	}

	public function testWithXml(): void
	{
		$module = new RateLimitStatus(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']);

		$request = new ServerRequest('GET', 'https://friendica.local/api/account/rate_limit_status');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertXml((string) $response->getBody(), 'hash');
	}
}
