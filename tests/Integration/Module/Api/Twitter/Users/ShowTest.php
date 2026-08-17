<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Users;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Show;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class ShowTest extends ApiTestCase
{
	public function testApiUsersShow(): void
	{
		$module = new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = new ServerRequest('GET', 'https://friendica.local/api/users/show');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals(static::SELF_USER['id'], $json->cid);
		self::assertEquals('DFRN', $json->location);
		self::assertEquals(static::SELF_USER['name'], $json->name);
		self::assertEquals(static::SELF_USER['nick'], $json->screen_name);
		self::assertTrue($json->verified);
	}

	public function testApiUsersShowWithXml(): void
	{
		$module = new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_XML,
		]);

		$request = new ServerRequest('GET', 'https://friendica.local/api/users/show');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertEquals(ICanCreateResponses::TYPE_XML, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}
}
