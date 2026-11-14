<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon\Statuses;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\Api\Mastodon\Statuses\Quotes;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class QuotesTest extends ApiTestCase
{
	// Public post of the fixture in api.fixture.php that is quoted by the posts below
	private const QUOTED_URI_ID = 1;

	// Public thread starters of the fixture. 4 is written by the contact 45, the other ones by the contact 43.
	private const QUOTING_URI_IDS = [3, 4, 5];

	protected function setUp(): void
	{
		parent::setUp();

		foreach (self::QUOTING_URI_IDS as $uriId) {
			Post\Quote::insert($uriId, ['quote-uri-id' => self::QUOTED_URI_ID]);
		}
	}

	/**
	 * @param array<string, string> $query
	 * @return string[] IDs of the returned statuses
	 */
	private function getQuoteIds(array $query = [], int $id = self::QUOTED_URI_ID): array
	{
		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/statuses/' . $id . '/quotes'))->withQueryParams($query);

		try {
			$this->createModule($id)->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertSame(200, $e->getResponse()->getStatusCode());
			return array_column($this->toJson($e->getResponse()), 'id');
		}
	}

	public function testQuotesAreListedNewestFirst(): void
	{
		self::assertSame(['5', '4', '3'], $this->getQuoteIds());
	}

	public function testLimit(): void
	{
		self::assertSame(['5', '4'], $this->getQuoteIds(['limit' => '2']));
	}

	public function testMaxId(): void
	{
		self::assertSame(['3'], $this->getQuoteIds(['max_id' => '4']));
	}

	public function testSinceId(): void
	{
		self::assertSame(['5', '4'], $this->getQuoteIds(['since_id' => '3']));
	}

	public function testMinId(): void
	{
		self::assertSame(['5', '4'], $this->getQuoteIds(['min_id' => '3']));
	}

	public function testMinIdWithLimitReturnsTheStatusesNextToIt(): void
	{
		self::assertSame(['4'], $this->getQuoteIds(['min_id' => '3', 'limit' => '1']));
	}

	/** Tusky sends the ID of the last loaded status as "offset" */
	public function testOffsetIsHandledLikeMaxId(): void
	{
		self::assertSame(['3'], $this->getQuoteIds(['offset' => '4']));
	}

	public function testOffsetIsIgnoredWhenAnotherBoundaryIsGiven(): void
	{
		self::assertSame(['4', '3'], $this->getQuoteIds(['max_id' => '5', 'offset' => '3']));
	}

	public function testQuotesOfBlockedContactsAreLeftOut(): void
	{
		self::assertTrue(DI::dba()->insert('user-contact', ['uid' => self::SELF_USER['id'], 'cid' => 45, 'blocked' => true]));

		self::assertSame(['5', '3'], $this->getQuoteIds());
	}

	public function testPostWithoutQuotesReturnsAnEmptyList(): void
	{
		self::assertSame([], $this->getQuoteIds([], 2));
	}

	public function testUnknownPostIsNotFound(): void
	{
		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/statuses/9999/quotes');

		try {
			$this->createModule(9999)->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertSame(404, $e->getResponse()->getStatusCode());
		}
	}

	private function createModule(int $id): Quotes
	{
		return new Quotes(DI::postInteractions(), DI::mstdnStatus(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['id' => (string) $id]);
	}
}
