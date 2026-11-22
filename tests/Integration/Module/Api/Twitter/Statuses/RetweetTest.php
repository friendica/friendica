<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Retweet;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class RetweetTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiStatusesRepeat(): void
	{
		$request = new ServerRequest('POST', 'https://friendica.local/api/statuses/retweet');

		$module = $this->createModule();

		$this->expectException(BadRequestException::class);

		$module->handleRequest($request);
	}

	public function testApiStatusesRepeatWithId(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/retweet'))
			->withParsedBody(['id' => 20001]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	public function testApiStatusesRepeatWithSharedId(): void
	{
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/retweet'))
			->withParsedBody(['id' => 20005]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	public function testApiStatusesRepeatWithoutAuthenticatedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/retweet'))
			->withParsedBody(['id' => 20001]);

		$module = $this->createModule();

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(): Retweet
	{
		return new Retweet(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}
