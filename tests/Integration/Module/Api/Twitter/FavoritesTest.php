<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\EarlyExitException;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Favorites;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class FavoritesTest extends ApiTestCase
{
	public function testApiFavorites(): void
	{
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/favorites'))
			->withQueryParams(['page' => '-1', 'max_id' => '20010']);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertNotEmpty($json);

		foreach ($json as $status) {
			self::assertStatus($status);
		}
	}

	public function testApiFavoritesWithRss(): void
	{
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$module = new Favorites(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_RSS,
		]);

		$request = new ServerRequest('GET', 'https://friendica.local/favorites.rss');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}

	public function testApiFavoritesWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/favorites');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(): Favorites
	{
		return new Favorites(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}
