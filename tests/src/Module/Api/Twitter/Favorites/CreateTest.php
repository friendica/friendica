<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Favorites;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Favorites\Create;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class CreateTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	/**
	 * Test the api_favorites_create_destroy() function with an invalid ID.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithInvalidId(): void
	{
		$this->expectException(BadRequestException::class);

		// @phpstan-ignore method.deprecated
		(new Create(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_favorites_create_destroy() function with the create action.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithCreateAction(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new Create(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id' => 3,
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	/**
	 * Test the api_favorites_create_destroy() function with the create action and an RSS result.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithCreateActionAndRss(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new Create(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => ICanCreateResponses::TYPE_RSS]))
			->run($this->httpExceptionMock, [
				'id' => 3,
			]);

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}
}
