<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Favorites;
use Friendica\Test\ApiTestCase;

class FavoritesTest extends ApiTestCase
{
	/**
	 * Test the api_favorites() function.
	 *
	 * @return void
	 */
	public function testApiFavorites(): void
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		// @phpstan-ignore method.deprecated
		$response = (new Favorites(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'page'   => -1,
				'max_id' => 20010,
			]);

		$json = $this->toJson($response);

		foreach ($json as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_favorites() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiFavoritesWithRss(): void
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		// @phpstan-ignore method.deprecated
		$response = (new Favorites(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_RSS,
		]))->run($this->httpExceptionMock);

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}
}
