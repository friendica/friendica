<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Network\HTTPClient\Client;

use Friendica\DI;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class HTTPClientTest extends MockedTestCase
{
	use DiceHttpMockHandlerTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
	}

	protected function tearDown(): void
	{
		$this->tearDownHandler();

		parent::tearDown();
	}

	/**
	 * Test for issue https://github.com/friendica/friendica/issues/10473#issuecomment-907749093
	 */
	public function testInvalidURI(): void
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(301, ['Location' => 'https:///']),
		]));

		self::assertFalse(DI::httpClient()->get('https://friendica.local')->isSuccess());
	}

	/**
	 * Test for issue https://github.com/friendica/friendica/issues/11726
	 */
	public function testRedirect(): void
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(302, ['Location' => 'https://mastodon.social/about']),
			new Response(200, ['Location' => 'https://mastodon.social']),
		]));

		$result = DI::httpClient()->get('https://mastodon.social');
		self::assertEquals('https://mastodon.social', $result->getUrl());
		self::assertEquals('https://mastodon.social/about', $result->getRedirectUrl());
	}
}
