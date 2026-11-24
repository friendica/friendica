<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon;

use Friendica\Core\EarlyExitException;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Module\Api\Mastodon\Search;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use Friendica\Test\Util\Database\StaticDatabaseWithFullTextSearch;
use GuzzleHttp\Psr7\ServerRequest;

final class SearchTest extends ApiTestCase
{
	protected string $databaseClass = StaticDatabaseWithFullTextSearch::class;

	public function testApiSearchReturnsAccounts(): void
	{
		$gserver = DBA::selectFirst('gserver', ['id'], ['nurl' => 'http://friendica.local']);
		DBA::update('gserver', ['failed' => 0, 'blocked' => 0], ['id' => $gserver['id']]);
		DBA::update('contact', ['gsid' => $gserver['id'], 'failed' => 0], ['id' => 3045]);

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v2/search'))
			->withQueryParams(['q' => 'friendcontact']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$json = $this->toJson($e->getResponse());

			self::assertNotEmpty($json->accounts);

			foreach ($json->accounts as $account) {
				self::assertStringContainsStringIgnoringCase('friendcontact', $account->acct);
			}
		}
	}

	public function testApiSearchReturnsStatuses(): void
	{
		DBA::insert('tag', [
			'id'   => 1000,
			'name' => 'reply',
			'url'  => '',
			'type' => Tag::HASHTAG,
		]);
		DBA::insert('post-tag', [
			'uri-id' => 20007,
			'type'   => Tag::HASHTAG,
			'tid'    => 1000,
			'cid'    => 0,
		]);

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v2/search'))
			->withQueryParams(['q' => '#reply']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$json = $this->toJson($e->getResponse());

			self::assertNotEmpty($json->statuses);
			self::assertCount(1, $json->statuses);
			self::assertEquals('20007', $json->statuses[0]->id);
		}
	}

	public function testApiSearchReturnsStatusesFromFullTextSearch(): void
	{
		DBA::insert('post-searchindex', [
			'uri-id'     => 20007,
			'owner-id'   => 3043,
			'searchtext' => 'This is a reply',
		]);

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v2/search'))
			->withQueryParams(['q' => 'reply']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$json = $this->toJson($e->getResponse());

			self::assertNotEmpty($json->statuses);
			self::assertCount(1, $json->statuses);
			self::assertEquals('20007', $json->statuses[0]->id);
		}
	}

	public function testApiSearchWithMaxIdReturnsOlderStatuses(): void
	{
		$this->insertSearchIndexEntries();

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v2/search'))
			->withQueryParams(['q' => 'reply', 'max_id' => '20007']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$json = $this->toJson($e->getResponse());

			self::assertCount(1, $json->statuses);
			self::assertEquals('20006', $json->statuses[0]->id);
		}
	}

	public function testApiSearchWithMinIdReturnsNewerStatuses(): void
	{
		$this->insertSearchIndexEntries();

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v2/search'))
			->withQueryParams(['q' => 'reply', 'min_id' => '20006']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$json = $this->toJson($e->getResponse());

			self::assertCount(1, $json->statuses);
			self::assertEquals('20007', $json->statuses[0]->id);
		}
	}

	public function testApiSearchWithLimitReturnsLimitedStatuses(): void
	{
		$this->insertSearchIndexEntries();

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v2/search'))
			->withQueryParams(['q' => 'reply', 'limit' => '1']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$json = $this->toJson($e->getResponse());

			self::assertCount(1, $json->statuses);
		}
	}

	public function testApiSearchWithoutQueryReturnsUnprocessableEntity(): void
	{
		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v2/search');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(422, $e->getResponse()->getStatusCode());
		}
	}

	public function testApiSearchWithUnallowedUserReturnsUnauthorized(): void
	{
		AuthTestConfig::$authenticated = false;

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v2/search'))
			->withQueryParams(['q' => 'test']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());
		}
	}

	private function insertSearchIndexEntries(): void
	{
		DBA::insert('post-searchindex', [
			'uri-id'     => 20006,
			'owner-id'   => 3043,
			'searchtext' => 'This is also a reply',
		]);
		DBA::insert('post-searchindex', [
			'uri-id'     => 20007,
			'owner-id'   => 3043,
			'searchtext' => 'This is a reply',
		]);
	}

	private function createModule(): Search
	{
		return new Search(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['version' => 2]);
	}
}
