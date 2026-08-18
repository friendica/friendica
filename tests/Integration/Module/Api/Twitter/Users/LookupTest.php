<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Users;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Lookup;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class LookupTest extends ApiTestCase
{
	public function testApiUsersLookup(): void
	{
		$module = new Lookup(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = new ServerRequest('GET', 'https://friendica.local/api/users/lookup');

		$this->expectException(NotFoundException::class);

		$module->handleRequest($request);
	}

	public function testApiUsersLookupWithUserId(): void
	{
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$module = new Lookup(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/users/lookup'))
			->withQueryParams(['user_id' => static::OTHER_USER['id']]);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertOtherUser($json[0]);
	}
}
