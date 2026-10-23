<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Worker;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Test\DatabaseTestCase;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Worker\ExpirePosts;

class ExpirePostsTest extends DatabaseTestCase
{
	private const OLD = '2020-01-01 00:00:00';

	protected function setUp(): void
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);

		DI::config()->set('system', 'dbclean-expire-limit', 1000);
		DI::config()->set('system', 'dbclean-expire-days', 30);
		DI::config()->set('system', 'dbclean-expire-unclaimed', 30);

		DBA::e('SET FOREIGN_KEY_CHECKS = 0');
	}

	protected function tearDown(): void
	{
		DBA::e('SET FOREIGN_KEY_CHECKS = 1');

		parent::tearDown();
	}

	/**
	 * A post that shares its uri-id with a contact (feed contacts) must survive the expiry,
	 * otherwise the cascade deletes the contact and hits the restricting post foreign keys.
	 */
	public function testExpiredThreadSharingUriIdWithContactIsKept(): void
	{
		$this->insertThread(9001, 9101, true);
		$this->insertThread(9002, 9102, false);

		DBA::e('SET FOREIGN_KEY_CHECKS = 1');
		$this->runExpiry();

		self::assertTrue(DBA::exists('item-uri', ['id' => 9001]));
		self::assertTrue(DBA::exists('contact', ['id' => 9101]));
		self::assertFalse(DBA::exists('item-uri', ['id' => 9002]));
	}

	public function testUnclaimedPublicPostSharingUriIdWithContactIsKept(): void
	{
		$this->insertUnclaimed(9003, 9103, true);
		$this->insertUnclaimed(9004, 9104, false);

		DBA::e('SET FOREIGN_KEY_CHECKS = 1');
		$this->runExpiry();

		self::assertTrue(DBA::exists('item-uri', ['id' => 9003]));
		self::assertTrue(DBA::exists('contact', ['id' => 9103]));
		self::assertFalse(DBA::exists('item-uri', ['id' => 9004]));
	}

	/**
	 * Remote posts that quote a local post must survive the expiry, since the quote authorization
	 * that we issued for them can only be served as long as the quoting post is present.
	 */
	public function testExpiredThreadQuotingLocalPostIsKept(): void
	{
		$this->insertThread(9005, 9105, false);
		$this->insertQuote(9005, 9205, true);
		$this->insertThread(9006, 9106, false);
		$this->insertQuote(9006, 9206, false);

		DBA::e('SET FOREIGN_KEY_CHECKS = 1');
		$this->runExpiry();

		self::assertTrue(DBA::exists('item-uri', ['id' => 9005]));
		self::assertFalse(DBA::exists('item-uri', ['id' => 9006]));
	}

	public function testUnclaimedPublicPostQuotingLocalPostIsKept(): void
	{
		$this->insertUnclaimed(9007, 9107, false);
		$this->insertQuote(9007, 9207, true);
		$this->insertUnclaimed(9008, 9108, false);
		$this->insertQuote(9008, 9208, false);

		DBA::e('SET FOREIGN_KEY_CHECKS = 1');
		$this->runExpiry();

		self::assertTrue(DBA::exists('item-uri', ['id' => 9007]));
		self::assertFalse(DBA::exists('item-uri', ['id' => 9008]));
	}

	private function runExpiry(): void
	{
		$method = new \ReflectionMethod(ExpirePosts::class, 'deleteExpiredExternalPosts');
		$method->invoke(null);
	}

	private function insertUri(int $id): void
	{
		DBA::insert('item-uri', ['id' => $id, 'uri' => 'https://example.test/' . $id, 'guid' => 'guid-' . $id]);
	}

	private function insertContact(int $id, int $uriId): void
	{
		DBA::insert('contact', [
			'id'      => $id,
			'uri-id'  => $uriId,
			'uid'     => 0,
			'url'     => 'https://example.test/' . $uriId,
			'nurl'    => 'https://example.test/' . $uriId,
			'network' => 'feed',
			'name'    => 'feed' . $id,
			'nick'    => 'feed' . $id,
		]);
	}

	private function insertThread(int $uriId, int $contactId, bool $contactUsesUri): void
	{
		$this->insertUri($uriId);
		$this->insertContact($contactId, $contactUsesUri ? $uriId : 0);
		DBA::insert('post-thread', ['uri-id' => $uriId, 'owner-id' => $contactId, 'author-id' => $contactId, 'received' => self::OLD]);
		DBA::insert('post-thread-user', ['uri-id' => $uriId, 'uid' => 0, 'owner-id' => $contactId, 'author-id' => $contactId, 'received' => self::OLD]);
	}

	private function insertQuote(int $uriId, int $quotedUriId, bool $quotedIsLocal): void
	{
		$this->insertUri($quotedUriId);
		if ($quotedIsLocal) {
			DBA::insert('post-origin', ['id' => $quotedUriId, 'uri-id' => $quotedUriId, 'uid' => 1, 'parent-uri-id' => $quotedUriId, 'thr-parent-id' => $quotedUriId, 'received' => self::OLD]);
		}
		DBA::insert('post', ['uri-id' => $uriId, 'parent-uri-id' => $uriId, 'thr-parent-id' => $uriId, 'received' => self::OLD]);
		DBA::insert('post-quote', ['uri-id' => $uriId, 'quote-uri-id' => $quotedUriId]);
	}

	private function insertUnclaimed(int $uriId, int $contactId, bool $contactUsesUri): void
	{
		$this->insertUri($uriId);
		$this->insertContact($contactId, $contactUsesUri ? $uriId : 0);
		DBA::insert('post-user', [
			'uri-id'   => $uriId, 'parent-uri-id' => $uriId, 'thr-parent-id' => $uriId, 'uid' => 0, 'gravity' => 0,
			'owner-id' => $contactId, 'author-id' => $contactId, 'received' => self::OLD,
		]);
	}
}
