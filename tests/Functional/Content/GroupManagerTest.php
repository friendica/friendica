<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Functional\Content;

use Friendica\Content\GroupManager;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GroupManagerTest extends FixtureTestCase
{
	private function getGroupManager(): GroupManager
	{
		return DI::getDice()->create(GroupManager::class);
	}

	public function testGetLinkForLocalGroup(): void
	{
		$contact = [
			'id'   => 123,
			'url'  => DI::baseUrl() . '/profile/localgroup',
			'addr' => 'localgroup@' . DI::baseUrl()->getHost(),
		];

		self::assertSame('group/localgroup', $this->getGroupManager()->getLink($contact));
	}

	public function testGetLinkForRemoteGroup(): void
	{
		$contact = [
			'id'   => 123,
			'url'  => 'https://example.org/c/remotegroup',
			'addr' => 'remotegroup@example.org',
		];

		self::assertSame('group/remotegroup%40example.org', $this->getGroupManager()->getLink($contact));
	}

	public function testGetLinkForRemoteGroupOnHostStartingWithLocalHost(): void
	{
		$host    = DI::baseUrl()->getHost();
		$contact = [
			'id'   => 123,
			'url'  => 'https://' . $host . '.example/c/remotegroup',
			'addr' => 'remotegroup@' . $host . '.example',
		];

		self::assertSame('group/' . rawurlencode('remotegroup@' . $host . '.example'), $this->getGroupManager()->getLink($contact));
	}

	private const UID     = 42;
	private const GROUP   = 9000;
	private const AUTHOR  = 9001;
	private const BLOCKED = 9002;

	private function insertContact(Database $dba, int $id): void
	{
		$dba->insert('item-uri', ['uri' => 'https://remote.example/contact/' . $id, 'guid' => 'contact-' . $id]);
		$dba->insert('contact', [
			'id'      => $id,
			'uid'     => 0,
			'uri-id'  => $dba->lastInsertId(),
			'url'     => 'https://remote.example/contact/' . $id,
			'nurl'    => 'https://remote.example/contact/' . $id,
			'name'    => 'Contact ' . $id,
			'nick'    => 'contact' . $id,
			'network' => 'apub',
		]);
	}

	/**
	 * Inserts a thread of the group (public for $uid = 0, otherwise private for that user) and returns its uri-id
	 */
	private function insertThread(Database $dba, int $number, int $author, bool $deleted, bool $unseen, int $uid = 0): int
	{
		$dba->insert('item-uri', ['uri' => 'https://remote.example/post/' . $number, 'guid' => 'post-' . $number]);
		$uriId = $dba->lastInsertId();

		$this->insertThreadCopy($dba, $uriId, $number, $author, $deleted, $unseen, $uid);

		return $uriId;
	}

	/**
	 * Inserts the copy of an existing thread for the given user
	 */
	private function insertThreadCopy(Database $dba, int $uriId, int $number, int $author, bool $deleted, bool $unseen, int $uid): void
	{
		$commented = '2026-01-0' . $number . ' 12:00:00';

		$dba->insert('post-user', [
			'uri-id'        => $uriId,
			'parent-uri-id' => $uriId,
			'thr-parent-id' => $uriId,
			'uid'           => $uid,
			'gravity'       => 0,
			'owner-id'      => self::GROUP,
			'author-id'     => $author,
			'visible'       => true,
			'deleted'       => $deleted,
			'unseen'        => $unseen,
			'created'       => $commented,
		]);

		$dba->insert('post-thread-user', [
			'uri-id'       => $uriId,
			'owner-id'     => self::GROUP,
			'author-id'    => $author,
			'uid'          => $uid,
			'post-user-id' => $dba->lastInsertId(),
			'deleted'      => $deleted,
			'commented'    => $commented,
		]);
	}

	private function prepareGroup(Database $dba): void
	{
		$this->insertContact($dba, self::GROUP);
		$this->insertContact($dba, self::AUTHOR);
		$this->insertContact($dba, self::BLOCKED);
		$dba->insert('user-contact', ['uid' => self::UID, 'cid' => self::BLOCKED, 'blocked' => true]);
	}

	public function testGetThreadIdsSkipsDeletedAndBlockedThreads(): void
	{
		$dba = DI::dba();
		$this->prepareGroup($dba);

		$visible = $this->insertThread($dba, 1, self::AUTHOR, false, false);
		$this->insertThread($dba, 2, self::AUTHOR, true, false);
		$this->insertThread($dba, 3, self::BLOCKED, false, false);

		self::assertSame([$visible], $this->getGroupManager()->getThreadIds(self::UID, self::GROUP, 0, 10));
	}

	public function testGetThreadIdsPagesOverHiddenThreads(): void
	{
		$dba = DI::dba();
		$this->prepareGroup($dba);

		$first = $this->insertThread($dba, 1, self::AUTHOR, false, false);
		$this->insertThread($dba, 2, self::BLOCKED, false, false);
		$third = $this->insertThread($dba, 3, self::AUTHOR, false, false);

		// Newest first, the blocked thread in between doesn't take a slot
		self::assertSame([$third], $this->getGroupManager()->getThreadIds(self::UID, self::GROUP, 0, 1));
		self::assertSame([$first], $this->getGroupManager()->getThreadIds(self::UID, self::GROUP, 1, 1));
	}

	public function testGetGroupStatsCountsUnreadThreads(): void
	{
		$dba = DI::dba();
		$this->prepareGroup($dba);

		$this->insertThread($dba, 1, self::AUTHOR, false, true, self::UID);
		$this->insertThread($dba, 2, self::AUTHOR, false, false, self::UID);
		$this->insertThread($dba, 3, self::AUTHOR, true, true, self::UID);

		$stats = $this->getGroupManager()->getGroupStats(self::UID, [self::GROUP]);

		// Only the first thread is unread, the third one is deleted
		self::assertSame(1, $stats[self::GROUP]['unread']);
	}

	public function testGetGroupStatsSkipsDeletedAndBlockedThreads(): void
	{
		$dba = DI::dba();
		$this->prepareGroup($dba);

		$this->insertThread($dba, 1, self::AUTHOR, false, false);
		$this->insertThread($dba, 2, self::AUTHOR, true, false);
		$this->insertThread($dba, 3, self::BLOCKED, false, false);
		$this->insertThread($dba, 4, self::AUTHOR, false, false, self::UID);

		$stats = $this->getGroupManager()->getGroupStats(self::UID, [self::GROUP]);

		// One public and one private thread remain
		self::assertSame(2, $stats[self::GROUP]['threads']);
		self::assertSame('2026-01-04 12:00:00', $stats[self::GROUP]['commented']);
	}

	public function testGetGroupStatsCountsThreadInBothCopiesOnce(): void
	{
		$dba = DI::dba();
		$this->prepareGroup($dba);

		$uriId = $this->insertThread($dba, 1, self::AUTHOR, false, false);
		$this->insertThreadCopy($dba, $uriId, 2, self::AUTHOR, false, false, self::UID);

		$stats = $this->getGroupManager()->getGroupStats(self::UID, [self::GROUP]);

		// The latest activity is taken from the newer copy
		self::assertSame(1, $stats[self::GROUP]['threads']);
		self::assertSame('2026-01-02 12:00:00', $stats[self::GROUP]['commented']);
	}

	public function testGetLinkWithoutAddress(): void
	{
		$contact = [
			'id'   => 123,
			'url'  => 'https://example.org/c/remotegroup',
			'addr' => '',
		];

		self::assertSame('group/123', $this->getGroupManager()->getLink($contact));
	}

	public static function dataServerDescription(): array
	{
		return [
			'lemmy-appended-description' => [
				'expected' => 'Lemmy Server',
				'name'     => 'Lemmy Server - A place for discussions about everything',
				'info'     => 'A place for discussions about everything',
			],
			'appended-description-with-html' => [
				'expected' => 'Lemmy Server',
				'name'     => 'Lemmy Server - A place for discussions about everything',
				'info'     => '<p>A place for discussions about <strong>everything</strong></p>',
			],
			'appended-shortened-description' => [
				'expected' => 'Lemmy Server',
				'name'     => 'Lemmy Server - A place for discussions about everything',
				'info'     => 'A place for discussions about everything!',
			],
			'dash-in-site-name' => [
				'expected' => 'Forum - Cats',
				'name'     => 'Forum - Cats - A place for discussions about everything',
				'info'     => 'A place for discussions about everything',
			],
			'dash-in-description' => [
				'expected' => 'Forum',
				'name'     => 'Forum - Cats - A place for discussions about everything',
				'info'     => 'Cats - A place for discussions about everything',
			],
			'dash-without-description' => [
				'expected' => 'Forum - Cats',
				'name'     => 'Forum - Cats',
				'info'     => 'A place for discussions about everything',
			],
			'empty-info' => [
				'expected' => 'Forum - Cats',
				'name'     => 'Forum - Cats',
				'info'     => '',
			],
			'empty-name' => [
				'expected' => 'example.org',
				'name'     => '',
				'info'     => 'A place for discussions about everything',
			],
		];
	}

	#[DataProvider('dataServerDescription')]
	public function testGetServerDescription(string $expected, string $name, string $info): void
	{
		$description = $this->getGroupManager()->getServerDescription(['site_name' => $name, 'info' => $info], 'example.org');

		self::assertSame($expected, $description['name']);
		self::assertSame('example.org', $description['host']);
		self::assertSame(trim(html_entity_decode(strip_tags($info))), $description['info']);
	}
}
