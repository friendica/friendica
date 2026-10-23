<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\ItemURI;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use Friendica\Util\DateTimeFormat;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class APContactTest extends MockedTestCase
{
	use DiceHttpMockHandlerTrait;

	// IP host: the HTTP client drops unresolvable host names before the mock handler.
	private const URL = 'https://192.0.2.10/users/alice';

	/** @var array<int, array<string, mixed>> */
	private array $requests = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
		DI::config()->set('system', 'block_private_addresses', false);

		$this->httpRequestHandler->setHandler(new MockHandler(array_fill(0, 10, new Response(404))));
		$this->httpRequestHandler->push(Middleware::history($this->requests));

		DBA::insert('apcontact', [
			'url'     => self::URL,
			'uri-id'  => ItemURI::getIdByURI(self::URL),
			'type'    => 'Person',
			'inbox'   => self::URL . '/inbox',
			'pubkey'  => 'key',
			'updated' => DateTimeFormat::utc('now - 2 months'),
		]);
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}

	public function testOutdatedContactIsNotRefetchedWhileRecentlyRequested(): void
	{
		DI::cache()->set('apcontact:' . ItemURI::getIdByURI(self::URL), 'previous request', Duration::FIVE_MINUTES);

		$apcontact = APContact::getByURL(self::URL);

		self::assertSame(self::URL, $apcontact['url']);
		self::assertSame([], $this->requests, 'No WebFinger or profile request within the repeat window.');
	}

	public function testOutdatedContactIsRefetched(): void
	{
		$apcontact = APContact::getByURL(self::URL);

		self::assertSame(self::URL, $apcontact['url']);
		self::assertNotSame([], $this->requests);
	}
}
