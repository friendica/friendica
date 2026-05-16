<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Lists;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Lists\Statuses;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class StatusesTest extends ApiTestCase
{
	/**
	 * Test the api_lists_statuses() function.
	 *
	 * @return void
	 */
	public function testApiListsStatuses()
	{
		$this->expectException(BadRequestException::class);

		(new Statuses(DI::dba(), DI::twitterStatus(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_lists_statuses() function with a list ID.
	 */
	public function testApiListsStatusesWithListId()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new Statuses(DI::dba(), DI::twitterStatus(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'list_id' => 1,
				'page'    => -1,
				'max_id'  => 10,
			]);

		$json = $this->toJson($response);

		foreach ($json as $status) {
			self::assertIsString($status->text);
			self::assertIsInt($status->id);
		}
	}

	/**
	 * Test the api_lists_statuses() function with a list ID and a RSS result.
	 */
	public function testApiListsStatusesWithListIdAndRss()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new Statuses(DI::dba(), DI::twitterStatus(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'rss']))
			->run($this->httpExceptionMock, [
				'list_id' => 1,
			]);

		self::assertXml((string) $response->getBody());
	}

	/**
	 * Test the api_lists_statuses() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiListsStatusesWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_lists_statuses('json');
	}
}
