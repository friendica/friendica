<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\NetworkPublicTimeline;
use Friendica\Test\ApiTestCase;

class NetworkPublicTimelineTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_networkpublic_timeline() function.
	 *
	 * @return void
	 */
	public function testApiStatusesNetworkpublicTimeline()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new NetworkPublicTimeline(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'max_id' => 10,
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
	 * Test the api_statuses_networkpublic_timeline() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesNetworkpublicTimelineWithNegativePage()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new NetworkPublicTimeline(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'page' => -2,
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
	 * Test the api_statuses_networkpublic_timeline() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesNetworkpublicTimelineWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_networkpublic_timeline('json');
	}

	/**
	 * Test the api_statuses_networkpublic_timeline() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesNetworkpublicTimelineWithRss()
	{
		// @todo: This call is needed for this test
		Renderer::registerTemplateEngine(\Friendica\Render\FriendicaSmartyEngine::class);

		$response = (new NetworkPublicTimeline(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'extension' => ICanCreateResponses::TYPE_RSS,
		]))->run($this->httpExceptionMock, [
			'page' => -2,
		]);

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}
}
