<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\UserTimeline;
use Friendica\Test\ApiTestCase;

class UserTimelineTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_user_timeline() function.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimeline(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new UserTimeline(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'user_id'         => 3043, // Public contact id
				'max_id'          => 20010,
				'exclude_replies' => true,
				'conversation_id' => 20001,
			]);

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertNotEmpty($json);
		foreach ($json as $status) {
			self::assertIsString($status->text);
			self::assertIsInt($status->id);
		}
	}

	/**
	 * Test the api_statuses_user_timeline() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithNegativePage(): void
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		// @phpstan-ignore method.deprecated
		$response = (new UserTimeline(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'user_id' => 3043, // Public contact id
				'page'    => -2,
			]);

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertNotEmpty($json);
		foreach ($json as $status) {
			self::assertIsString($status->text);
			self::assertIsInt($status->id);
		}
	}

	/**
	 * Test the api_statuses_user_timeline() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithRss(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new UserTimeline(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_RSS,
		]))->run($this->httpExceptionMock);

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}
}
