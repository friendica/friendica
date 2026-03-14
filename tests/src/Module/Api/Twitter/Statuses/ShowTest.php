<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
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
	public function testApiStatusesShow()
	{
		$this->expectException(BadRequestException::class);


		(new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_statuses_show() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithId()
	{
		$response = (new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id' => 1,
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
	public function testApiStatusesShowWithConversation()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'id'           => 1,
				'conversation' => 1,
			]);

		$json = $this->toJson($response);

		self::assertIsArray($json);

		foreach ($json as $status) {
			self::assertIsInt($status->id);
			self::assertIsString($status->text);
		}
	}

	/**
	 * Test the api_statuses_show() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_show('json');
	}
}
