<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Account;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Account\UpdateProfile;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class UpdateProfileTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiAccountUpdateProfile(): void
	{
		$module = new UpdateProfile(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/account/update_profile'))
			->withParsedBody([
				'name'        => 'new_name',
				'description' => 'new_description',
			]);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('new_name', $json->name);
		self::assertEquals('new_description', $json->description);
	}
}
