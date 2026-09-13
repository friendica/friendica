<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Friendships;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Friendships\Incoming;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class IncomingTest extends ApiTestCase
{
	public function testApiFriendshipsIncoming(): void
	{
		DI::dba()->insert('intro', [
			'uid'        => 42,
			'contact-id' => 43,
		]);

		$request = new ServerRequest('GET', 'https://friendica.local/api/friendships/incoming');

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json->ids);
		self::assertSame([43], $json->ids);
		self::assertSame(1, $json->total_count);
	}

	public function testApiFriendshipsIncomingWithUndefinedCursor(): void
	{
		DI::dba()->insert('intro', [
			'uid'        => 42,
			'contact-id' => 43,
		]);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/friendships/incoming'))
			->withQueryParams(['cursor' => 'undefined']);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json->ids);
		self::assertSame([43], $json->ids);
		self::assertSame(1, $json->total_count);
	}

	public function testApiFriendshipsIncomingWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = new ServerRequest('GET', 'https://friendica.local/api/friendships/incoming');

		try {
			$this->createModule()->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(): Incoming
	{
		return new Incoming(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}
