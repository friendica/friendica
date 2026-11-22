<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon\Accounts;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Mastodon\Accounts\Statuses;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class StatusesTest extends ApiTestCase
{
	public function testApiStatusShowWithJson(): void
	{
		$module = $this->createModule(3043);

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/accounts/3043/statuses');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());
			self::assertNotEmpty($statuses);
			self::assertCount(4, $statuses);
			self::assertEquals(['20005', '20003', '20002', '20001'], array_column($statuses, 'id'));
			self::assertObjectHasProperty('id', $statuses[0]);
		}
	}

	public function testApiStatusShowWithXml(): void
	{
		$module = $this->createModule(3045);

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/accounts/3045/statuses');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertNotEmpty($statuses);
			self::assertCount(4, $statuses);
			self::assertEquals(['20100', '20007', '20006', '20004'], array_column($statuses, 'id'));
			self::assertObjectHasProperty('id', $statuses[0]);
		}
	}

	private function createModule(int $contactId): Statuses
	{
		return new Statuses(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['id' => (string) $contactId]);
	}
}
