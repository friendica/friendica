<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Update;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use Friendica\Util\DateTimeFormat;
use GuzzleHttp\Psr7\ServerRequest;

final class UpdateTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiStatusesUpdate(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/update'))
			->withParsedBody([
				'status'                => 'Status content #friendica',
				'in_reply_to_status_id' => 0,
				'lat'                   => 48,
				'long'                  => 7,
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
		self::assertStringContainsString('Status content #friendica', $json->text);
		self::assertStringContainsString('Status content #', $json->statusnet_html);
	}

	public function testApiStatusesUpdateWithHtml(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/update'))
			->withParsedBody(['htmlstatus' => '<b>Status content</b>']);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	public function testApiStatusesUpdateWithParent(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/update'))
			->withParsedBody([
				'status'                => 'Status content',
				'in_reply_to_status_id' => 20001,
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	public function testApiStatusesUpdateWithMediaIds(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/update'))
			->withParsedBody([
				'status'    => 'Status content',
				'media_ids' => '1',
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	public function testApiStatusesUpdateWithDayThrottleReached(): void
	{
		DI::config()->set('system', 'throttle_limit_day', 1);

		DBA::update('post-thread-user', ['received' => DateTimeFormat::utcNow()], ['uri-id' => [20001, 20003], 'uid' => 1042]);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/update'))
			->withParsedBody(['status' => 'Status content']);

		$module = $this->createModule();

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(429, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	public function testApiStatusesUpdateWithoutAuthenticatedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = (new ServerRequest('POST', 'https://friendica.local/api/statuses/update'))
			->withParsedBody(['status' => 'Status content']);

		$module = $this->createModule();

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(): Update
	{
		return new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}
