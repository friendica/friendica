<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
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
		$response = (new VerifyCredentials(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertEquals(48, $json->id);
		self::assertIsArray($json->emojis);
		self::assertIsArray($json->fields);
	}

	/**
	 * Test the api_account_verify_credentials() function without an authenticated user.
	 *
	 */
	public function testApiAccountVerifyCredentialsWithoutAuthenticatedUser(): void
	{
		self::markTestIncomplete('Needs dynamic BasicAuth first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// $_SESSION['authenticated'] = false;
		// api_account_verify_credentials('json');
	}
}
