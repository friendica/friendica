<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Friendships;

use Friendica\DI;
use Friendica\Module\Api\Twitter\Friendships\Incoming;
use Friendica\Test\ApiTestCase;

class IncomingTest extends ApiTestCase
{
	/**
	 * Test the api_friendships_incoming() function.
	 *
	 * @return void
	 */
	public function testApiFriendshipsIncoming(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new Incoming(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertIsArray($json->ids);
	}
}
