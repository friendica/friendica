<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Users;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Search;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class SearchTest extends ApiTestCase
{
	public function testApiUsersSearch(): void
	{
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$module = new Search(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/users/search'))
			->withQueryParams(['q' => static::OTHER_USER['name']]);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertOtherUser($json[0]);
	}

	public function testApiUsersSearchWithXml(): void
	{
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$module = new Search(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_XML,
		]);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/users/search'))
			->withQueryParams(['q' => static::OTHER_USER['name']]);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertXml((string) $response->getBody(), 'users');
	}

	public function testApiUsersSearchWithoutQuery(): void
	{
		$module = new Search(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = new ServerRequest('GET', 'https://friendica.local/api/users/search');

		$this->expectException(BadRequestException::class);

		$module->handleRequest($request);
	}
}
