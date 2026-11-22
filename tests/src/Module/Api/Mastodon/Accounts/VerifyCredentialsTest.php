<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Mastodon\Accounts;

use Friendica\DI;
use Friendica\Module\Api\Mastodon\Accounts\VerifyCredentials;
use Friendica\Test\ApiTestCase;

class VerifyCredentialsTest extends ApiTestCase
{
	/**
	 * Test the api_account_verify_credentials() function.
	 *
	 * @return void
	 */
	public function testApiAccountVerifyCredentials(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new VerifyCredentials(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertEquals(3048, $json->id);
		self::assertIsArray($json->emojis);
		self::assertIsArray($json->fields);
	}
}
