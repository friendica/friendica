<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Retweet;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class RetweetTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	/**
	 * Test the api_statuses_repeat() function.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeat(): void
	{
		$this->expectException(BadRequestException::class);

		// @phpstan-ignore method.deprecated
		(new Retweet(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_statuses_repeat() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeatWithId(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new Retweet(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id' => 20001,
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	/**
	 * Test the api_statuses_repeat() function with an shared ID.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeatWithSharedId(): void
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		// @phpstan-ignore method.deprecated
		$response = (new Retweet(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id' => 20005,
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}
}
