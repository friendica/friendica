<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Mentions;
use Friendica\Test\ApiTestCase;

class MentionsTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_mentions() function.
	 *
	 * @return void
	 */
	public function testApiStatusesMentions(): void
	{
		$response = (new Mentions(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'max_id' => 10,
			]);

		$json = $this->toJson($response);

		self::assertEmpty($json);
		// We should test with mentions in the database.
	}

	/**
	 * Test the api_statuses_mentions() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithNegativePage(): void
	{
		$response = (new Mentions(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'page' => -2,
			]);

		$json = $this->toJson($response);

		self::assertEmpty($json);
		// We should test with mentions in the database.
	}

	/**
	 * Test the api_statuses_mentions() function with an unallowed user.
	 *
	 */
	public function testApiStatusesMentionsWithUnallowedUser(): void
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_mentions('json');
	}

	/**
	 * Test the api_statuses_mentions() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithRss(): void
	{
		$response = (new Mentions(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => ICanCreateResponses::TYPE_RSS]))
			->run($this->httpExceptionMock, [
				'page' => -2,
			]);

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}
}
