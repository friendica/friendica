<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon\Timelines;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Mastodon\Timelines\PublicTimeline;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class PublicTimelineTest extends ApiTestCase
{
	public function testApiStatusesPublicTimeline(): void
	{
		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/public');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(4, $statuses);
			self::assertEquals(['20007', '20006', '20003', '20001'], array_column($statuses, 'id'));
		}
	}

	public function testApiStatusesPublicTimelineWithComments(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/public'))
			->withQueryParams(['exclude_replies' => 'false']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(8, $statuses);
			self::assertEquals(['20100', '20007', '20006', '20005', '20004', '20003', '20002', '20001'], array_column($statuses, 'id'));
		}
	}

	public function testApiStatusesPublicTimelineWithMaxId(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/public'))
			->withQueryParams(['max_id' => '20007']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(3, $statuses);
			self::assertEquals(['20006', '20003', '20001'], array_column($statuses, 'id'));
		}
	}

	public function testApiStatusesPublicTimelineWithLimit(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/public'))
			->withQueryParams(['limit' => '2']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(2, $statuses);
			self::assertEquals(['20007', '20006'], array_column($statuses, 'id'));
		}
	}

	public function testApiStatusesPublicTimelineWithLocal(): void
	{
		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/public'))
			->withQueryParams(['local' => 'true']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertCount(3, $statuses);
			self::assertEquals('20006', $statuses[0]->id);
			self::assertEquals('20003', $statuses[1]->id);
			self::assertEquals('20001', $statuses[2]->id);
		}
	}

	public function testApiStatusesPublicTimelineWithUnallowedUser(): void
	{
		DI::config()->set('system', 'block_public', true);
		AuthTestConfig::$authenticated = false;

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/timelines/public');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());
		}
	}

	private function createModule(): PublicTimeline
	{
		return new PublicTimeline(DI::config(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['version' => 2]);
	}
}
