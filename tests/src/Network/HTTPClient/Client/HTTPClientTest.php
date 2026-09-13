<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Network\HTTPClient\Client;

use Friendica\DI;
use Friendica\Util\Network;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;

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

	public static function privateTargetProvider(): array
	{
		return [
			'loopback'       => ['http://127.0.0.1:9999/'],
			'loopback v6'    => ['http://[::1]:9999/'],
			'private'        => ['http://10.1.2.3/'],
			'private 192'    => ['http://192.168.178.1/'],
			'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
			'v4 mapped'      => ['http://[::ffff:127.0.0.1]/'],
		];
	}

	/** Ensures private targets are not requested. */
	#[DataProvider('privateTargetProvider')]
	public function testPrivateTargetIsNotRequested(string $url): void
	{
		$mock = new MockHandler([new Response(200, [], 'internal secret')]);
		$this->httpRequestHandler->setHandler($mock);

		self::assertFalse(DI::httpClient()->get($url)->isSuccess());
		self::assertCount(1, $mock, 'the request was sent even though the target is private');
	}

	/** Ensures redirects to private targets are not followed. */
	public function testRedirectToPrivateTargetIsNotFollowed(): void
	{
		$mock = new MockHandler([
			new Response(302, ['Location' => 'http://127.0.0.1:9999/redir-target']),
			new Response(200, [], 'internal secret'),
		]);
		$this->httpRequestHandler->setHandler($mock);

		self::assertFalse(DI::httpClient()->get('https://mastodon.social')->isSuccess());
		self::assertCount(1, $mock, 'the redirect into the private network was followed');
	}

	public static function unsupportedSchemeProvider(): array
	{
		return [
			'file'   => ['file://localhost/etc/passwd'],
			'gopher' => ['gopher://localhost/1'],
			'ftp'    => ['ftp://localhost/file'],
		];
	}

	#[DataProvider('unsupportedSchemeProvider')]
	public function testUnsupportedSchemeIsRejected(string $url): void
	{
		$mock = new MockHandler([new Response(200)]);
		$this->httpRequestHandler->setHandler($mock);

		self::assertFalse(DI::httpClient()->get($url)->isSuccess());
		self::assertCount(1, $mock);
	}

	/** Ensures public targets remain reachable. */
	public function testPublicTargetIsStillRequested(): void
	{
		$this->httpRequestHandler->setHandler(new MockHandler([new Response(200, [], 'hello')]));

		$result = DI::httpClient()->get('https://mastodon.social');
		self::assertTrue($result->isSuccess());
		self::assertEquals('hello', $result->getBodyString());
	}

	/** Ensures the node can request its own base URL. */
	public function testOwnBaseUrlIsNeverPrivate(): void
	{
		self::assertFalse(Network::isPrivateTarget(DI::baseUrl()));
	}

	public function testAllowedInternalHostIsRequested(): void
	{
		DI::config()->set('system', 'allowed_internal_hosts', '127.0.0.1');

		$this->httpRequestHandler->setHandler(new MockHandler([new Response(200, [], 'hello')]));

		self::assertTrue(DI::httpClient()->get('http://127.0.0.1:9999/thumb.jpg')->isSuccess());
	}

	public function testCheckCanBeDisabled(): void
	{
		DI::config()->set('system', 'block_private_addresses', false);

		$this->httpRequestHandler->setHandler(new MockHandler([new Response(200, [], 'internal')]));

		self::assertTrue(DI::httpClient()->get('http://127.0.0.1:9999/')->isSuccess());
	}
}
