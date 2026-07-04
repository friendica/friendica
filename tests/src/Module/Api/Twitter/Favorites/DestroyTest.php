<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Favorites;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Favorites\Destroy;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class DestroyTest extends ApiTestCase
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

		(new Destroy(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_favorites_create_destroy() function with the destroy action.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithDestroyAction(): void
	{
		$response = (new Destroy(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id' => 3,
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	/**
	 * Test the api_favorites_create_destroy() function without an authenticated user.
	 *
	 */
	public function testApiFavoritesCreateDestroyWithoutAuthenticatedUser(): void
	{
		self::markTestIncomplete('Needs refactoring of Lists - replace filter_input() with $request parameter checks');

		/*
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		DI::args()->setArgv(['api', '1.1', 'favorites', 'create.json']);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_favorites_create_destroy('json');
		*/
	}
}
