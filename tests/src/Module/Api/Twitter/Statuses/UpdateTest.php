<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Update;
use Friendica\Test\ApiTestCase;

class UpdateTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	/**
	 * Test the api_statuses_update() function.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdate(): void
	{
		$_FILES = [
			'media' => [
				'id'       => 666,
				'size'     => 666,
				'width'    => 666,
				'height'   => 666,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer.png',
				'type'     => 'image/png',
			],
		];

		$response = (new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'status'                => 'Status content #friendica',
				'in_reply_to_status_id' => 0,
				'lat'                   => 48,
				'long'                  => 7,
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
		self::assertStringContainsString('Status content #friendica', $json->text);
		self::assertStringContainsString('Status content #', $json->statusnet_html);
	}

	/**
	 * Test the api_statuses_update() function with an HTML status.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithHtml(): void
	{
		$response = (new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'htmlstatus' => '<b>Status content</b>',
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	/**
	 * Test the api_statuses_update() function without an authenticated user.
	 *
	 */
	public function testApiStatusesUpdateWithoutAuthenticatedUser(): void
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_statuses_update('json');
		*/
	}

	/**
	 * Test the api_statuses_update() function with a parent status.
	 *
	 */
	public function testApiStatusesUpdateWithParent(): void
	{
		$this->markTestIncomplete('This triggers an exit() somewhere and kills PHPUnit.');
	}

	/**
	 * Test the api_statuses_update() function with a media_ids parameter.
	 *
	 */
	public function testApiStatusesUpdateWithMediaIds(): void
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_statuses_update() function with the throttle limit reached.
	 *
	 */
	public function testApiStatusesUpdateWithDayThrottleReached(): void
	{
		$this->markTestIncomplete();
	}
}
