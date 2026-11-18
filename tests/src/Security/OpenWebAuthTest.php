<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Security;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Security\OpenWebAuth;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class OpenWebAuthTest extends MockedTestCase
{
	use DiceHttpMockHandlerTrait;

	// IP host: the HTTP client drops unresolvable host names before the mock handler.
	private const UNKNOWN_SIGNER = 'https://192.0.2.10/users/alice';

	/** @var array<int, array<string, mixed>> */
	private array $requests = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
		DI::config()->set('system', 'block_private_addresses', false);

		$this->httpRequestHandler->setHandler(new MockHandler(array_fill(0, 10, new Response(404))));
		$this->httpRequestHandler->push(Middleware::history($this->requests));
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}

	/**
	 * Probing the signer of an incoming request calls back the remote system,
	 * which can wait on this very request before it answers.
	 */
	public function testUnknownSignerIsNotProbed(): void
	{
		self::assertSame([], OpenWebAuth::addVisitorCookieForHandle(self::UNKNOWN_SIGNER, false));
		self::assertSame([], $this->requests);
	}

	public function testUnknownSignerIsProbedInTheBackgroundOnce(): void
	{
		OpenWebAuth::addVisitorCookieForHandle(self::UNKNOWN_SIGNER, false);
		OpenWebAuth::addVisitorCookieForHandle(self::UNKNOWN_SIGNER, false);

		self::assertSame(1, $this->countAddContactTasks(self::UNKNOWN_SIGNER));
	}

	public function testKnownSignerIsAuthenticated(): void
	{
		$visitor = OpenWebAuth::addVisitorCookieForHandle('https://domain.tld/profile/remotecontact', false);

		self::assertSame('https://domain.tld/profile/remotecontact', $visitor['url']);
		self::assertSame([], $this->requests);
		self::assertSame(0, $this->countAddContactTasks('https://domain.tld/profile/remotecontact'));
	}

	private function countAddContactTasks(string $url): int
	{
		return DBA::count('workerqueue', ['command' => 'AddContact', 'parameter' => json_encode([0, $url, '']), 'done' => false]);
	}
}
