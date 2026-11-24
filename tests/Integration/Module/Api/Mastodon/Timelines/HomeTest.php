<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon\Timelines;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Mastodon\Timelines\Home;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class HomeTest extends ApiTestCase
{
	public function testApiStatusesHomeTimeline(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/home'))
			->withQueryParams(['max_id' => '20010', 'exclude_replies' => 'true']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(3, $statuses);
			self::assertEquals(['20006', '20003', '20001'], array_column($statuses, 'id'));
		}
	}

	public function testApiStatusesHomeTimelineWithNegativePage(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/home'))
			->withQueryParams(['min_id' => '20001']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(2, $statuses);
			self::assertEquals(['20006', '20003'], array_column($statuses, 'id'));
		}
	}

	public function testApiStatusesHomeTimelineWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/home');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());
		}
	}

	public function testApiStatusesHomeTimelineWithRss(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/home'))
			->withQueryParams(['exclude_replies' => 'false']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(6, $statuses);
			self::assertEquals(['20006', '20005', '20004', '20003', '20002', '20001'], array_column($statuses, 'id'));
		}
	}

	private function createModule(): Home
	{
		return new Home(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}
