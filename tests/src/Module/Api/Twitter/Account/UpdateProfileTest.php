<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Account;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Account\UpdateProfile;
use Friendica\Test\ApiTestCase;

class UpdateProfileTest extends ApiTestCase
{
	/**
	 * Test the api_account_update_profile() function.
	 */
	public function testApiAccountUpdateProfile(): void
	{
		$this->useHttpMethod(Router::POST);

		$response = (new UpdateProfile(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json'])) // @phpstan-ignore method.deprecated
			->run($this->httpExceptionMock, [
				'name'        => 'new_name',
				'description' => 'new_description',
			]);

		$json = $this->toJson($response);

		self::assertEquals('new_name', $json->name);
		self::assertEquals('new_description', $json->description);
	}

	public function testHandleRequestAccountUpdateProfileReturnsUser(): void
	{
		$this->useHttpMethod(Router::POST);

		$module = new UpdateProfile(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn([
			'name'        => 'new_name',
			'description' => 'new_description',
		]);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$json = $this->toJson($response);

		self::assertEquals('new_name', $json->name);
		self::assertEquals('new_description', $json->description);
	}
}
