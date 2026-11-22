<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Show;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class ShowTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_show() function.
	 *
	 * @return void
	 */
	public function testApiStatusesShow(): void
	{
		$this->expectException(BadRequestException::class);

		// @phpstan-ignore method.deprecated
		(new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_statuses_show() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithId(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id' => 20001,
			]);

		$json = $this->toJson($response);

		self::assertIsInt($json->id);
		self::assertIsString($json->text);
	}

	/**
	 * Test the api_statuses_show() function with the conversation parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithConversation(): void
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		// @phpstan-ignore method.deprecated
		$response = (new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id'           => 20001,
				'conversation' => 20001,
			]);

		$json = $this->toJson($response);

		self::assertIsArray($json);

		foreach ($json as $status) {
			self::assertIsInt($status->id);
			self::assertIsString($status->text);
		}
	}
}
