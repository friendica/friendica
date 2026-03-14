<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Search;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class SearchTest extends ApiTestCase
{
	/**
	 * Test the api_users_search() function.
	 *
	 * @return void
	 */
	public function testApiUsersSearch()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new Search(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'q' => static::OTHER_USER['name'],
			]);

		$json = $this->toJson($response);

		self::assertOtherUser($json[0]);
	}

	/**
	 * Test the api_users_search() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiUsersSearchWithXml()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new Search(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_XML,
		]))->run($this->httpExceptionMock, [
			'q' => static::OTHER_USER['name'],
		]);

		self::assertXml((string) $response->getBody(), 'users');
	}

	/**
	 * Test the api_users_search() function without a GET q parameter.
	 *
	 * @return void
	 */
	public function testApiUsersSearchWithoutQuery()
	{
		$this->expectException(BadRequestException::class);

		(new Search(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}
}
