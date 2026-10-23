<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Friends;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Friends\Lists;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class ListsTest extends ApiTestCase
{
	public function testApiStatusesFWithFriends(): void
	{
		$request = new ServerRequest('GET', 'https://friendica.local/api/friends/list');

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
		self::assertSame(2, $json->total_count);
		self::assertSame([47, 45], array_map(static function ($user) {
			return $user->pid;
		}, $json->users));
	}

	public function testApiStatusesFriendsWithUndefinedCursor(): void
	{
		$request = (new ServerRequest('GET', 'https://friendica.local/api/friends/list'))
			->withQueryParams(['cursor' => 'undefined']);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
		self::assertSame(2, $json->total_count);
		self::assertSame([47, 45], array_map(static function ($user) {
			return $user->pid;
		}, $json->users));
	}

	public function testApiStatusesFriendsWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = new ServerRequest('GET', 'https://friendica.local/api/friends/list');

		try {
			$this->createModule()->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(): Lists
	{
		return new Lists(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}
