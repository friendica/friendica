<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Account;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Account\RateLimitStatus;
use Friendica\Test\ApiTestCase;

class RateLimitStatusTest extends ApiTestCase
{
	public function testWithJson(): void
	{
		$response = (new RateLimitStatus(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock);

		$result = $this->toJson($response);

		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());
		self::assertEquals(150, $result->remaining_hits);
		self::assertEquals(150, $result->hourly_limit);
		self::assertIsInt($result->reset_time_in_seconds);
	}

	public function testWithXml(): void
	{
		$response = (new RateLimitStatus(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']))
			->run($this->httpExceptionMock);

		self::assertEquals([
			'Content-type'                => ['text/xml'],
			ICanCreateResponses::X_HEADER => ['xml'],
		], $response->getHeaders());
		self::assertXml($response->getBody(), 'hash');
	}
}
