<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Show;
use Friendica\Test\ApiTestCase;

class ShowTest extends ApiTestCase
{
	/**
	 * Test the api_users_show() function.
	 *
	 * @return void
	 */
	public function testApiUsersShow(): void
	{
		$response = (new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		// We can't use assertSelfUser() here because the user object is missing some properties.
		self::assertEquals(static::SELF_USER['id'], $json->cid);
		self::assertEquals('DFRN', $json->location);
		self::assertEquals(static::SELF_USER['name'], $json->name);
		self::assertEquals(static::SELF_USER['nick'], $json->screen_name);
		self::assertTrue($json->verified);
	}

	/**
	 * Test the api_users_show() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiUsersShowWithXml(): void
	{
		$response = (new Show(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_XML,
		]))->run($this->httpExceptionMock);

		self::assertEquals(ICanCreateResponses::TYPE_XML, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}
}
