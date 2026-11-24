<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Statuses;

use Friendica\Core\EarlyExitException;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Show;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class ShowTest extends ApiTestCase
{
	public function testApiStatusesShow(): void
	{
		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/statuses/show');

		$this->expectException(BadRequestException::class);

		$module->handleRequest($request);
	}

	public function testApiStatusesShowWithId(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/statuses/show'))
			->withQueryParams(['id' => '20001']);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	public function testApiStatusesShowWithConversation(): void
	{
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/statuses/show'))
			->withQueryParams(['id' => '20001', 'conversation' => '20001']);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertNotEmpty($json);
		foreach ($json as $status) {
			self::assertStatus($status);
		}
	}

	public function testApiStatusesShowWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/statuses/show');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(): Show
	{
		return new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}
