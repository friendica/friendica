<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Model;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\Delivery;
use Friendica\Test\FixtureTestCase;

/**
 * Pins the current behaviour of the deletion path before it moves out of Model\Item.
 *
 * Fixture threads of user 42 in api.fixture.php, written as user item / public copy:
 * - thread 1: parent 1/7 with the comments 2/8, 4/10 and 5/11
 * - thread 3: parent 3/9
 * - thread 6: parent 6/12
 * - thread 7: public copy 13 with the comment 14, no user item
 */
class ItemDeletionTest extends FixtureTestCase
{
	private const UID              = 42;
	private const OTHER_UID        = 43;
	private const THREAD_1         = [1, 2, 4, 5, 7, 8, 10, 11];
	private const NODEINFO_COUNTER = ['nodeinfo_total_posts', 'nodeinfo_total_comments', 'nodeinfo_local_posts', 'nodeinfo_local_comments'];

	protected function setUp(): void
	{
		parent::setUp();

		// The key-value storage is not part of the fixtures
		foreach (self::NODEINFO_COUNTER as $counter) {
			DI::keyValue()->set($counter, 10);
		}
	}

	/**
	 * @return int[]
	 */
	private function getDeletedIds(): array
	{
		$ids = array_column(DBA::selectToArray('post-user', ['id'], ['deleted' => true], ['order' => ['id']]), 'id');

		return array_map(intval(...), $ids);
	}

	/**
	 * @return array<int, array{string, int, int}> The parameters of the queued Notifier jobs
	 */
	private function getNotifierJobs(): array
	{
		$jobs = DBA::selectToArray('workerqueue', ['parameter'], ['command' => 'Notifier'], ['order' => ['id']]);

		return array_map(fn (array $job): array => json_decode((string) $job['parameter'], true), $jobs);
	}

	/**
	 * @return array<string, int>
	 */
	private function getNodeinfoChanges(): array
	{
		$changes = [];
		foreach (self::NODEINFO_COUNTER as $counter) {
			$changes[$counter] = DI::keyValue()->get($counter) - 10;
		}

		return array_filter($changes);
	}

	private function isHiddenForUser(int $uriId): bool
	{
		return DBA::exists('post-user', ['uri-id' => $uriId, 'uid' => self::UID, 'hidden' => true]);
	}

	/**
	 * Gives a second user a copy of thread 3 and returns the id of that item.
	 */
	private function copyThread3ToOtherUser(): int
	{
		DBA::insert('user', ['uid' => self::OTHER_UID, 'nickname' => 'other']);

		$item = DBA::selectFirst('post-user', [], ['id' => 3]);
		unset($item['id']);
		DBA::insert('post-user', ['uid' => self::OTHER_UID, 'origin' => false] + $item);
		$id = DBA::lastInsertId();

		$thread = DBA::selectFirst('post-thread-user', [], ['uri-id' => 3, 'uid' => self::UID]);
		DBA::insert('post-thread-user', ['uid' => self::OTHER_UID, 'origin' => false, 'post-user-id' => $id] + $thread);

		return $id;
	}

	public function testUnknownItemIsNotDeleted(): void
	{
		self::assertFalse(Item::markForDeletionById(999));
		self::assertSame([], $this->getDeletedIds());
	}

	public function testItemIsOnlyDeletedOnce(): void
	{
		self::assertTrue(Item::markForDeletionById(3));
		self::assertFalse(Item::markForDeletionById(3));

		self::assertCount(1, $this->getNotifierJobs());
	}

	public function testOwnCommentTakesItsPublicCopyAlong(): void
	{
		self::assertTrue(Item::markForDeletionById(2));

		self::assertSame([2, 8], $this->getDeletedIds());
		self::assertSame([[Delivery::DELETION, 2, self::UID]], $this->getNotifierJobs());
		self::assertSame(['nodeinfo_total_comments' => -1, 'nodeinfo_local_comments' => -1], $this->getNodeinfoChanges());
	}

	public function testOwnThreadTakesAllCommentsAlong(): void
	{
		self::assertTrue(Item::markForDeletionById(1));

		self::assertSame(self::THREAD_1, $this->getDeletedIds());
		self::assertFalse(DBA::exists('post-engagement', ['uri-id' => 1]));
	}

	public function testOwnThreadNotifiesForEveryOwnItem(): void
	{
		Item::markForDeletionById(1);

		$notified = array_column($this->getNotifierJobs(), 1);
		sort($notified);

		self::assertSame([1, 2, 4, 5], $notified);
		self::assertSame(
			['nodeinfo_total_posts' => -1, 'nodeinfo_total_comments' => -3, 'nodeinfo_local_posts' => -1, 'nodeinfo_local_comments' => -3],
			$this->getNodeinfoChanges(),
		);
	}

	public function testThreadLeavesOtherThreadsAlone(): void
	{
		self::assertTrue(Item::markForDeletionById(6));

		self::assertSame([6, 12], $this->getDeletedIds());
	}

	public function testPublicThreadTakesItsCommentsAlong(): void
	{
		self::assertTrue(Item::markForDeletionById(13));

		self::assertSame([13, 14], $this->getDeletedIds());
	}

	public function testOwnPostTakesTheCopiesOfOtherUsersAlong(): void
	{
		$copyId = $this->copyThread3ToOtherUser();

		self::assertTrue(Item::markForDeletionById(3));

		self::assertSame([3, 9, $copyId], $this->getDeletedIds());
		self::assertSame([[Delivery::DELETION, 3, self::UID]], $this->getNotifierJobs());
	}

	public function testCopyOfOtherUserLeavesTheOriginalAlone(): void
	{
		$copyId = $this->copyThread3ToOtherUser();

		self::assertTrue(Item::markForDeletionById($copyId));

		self::assertSame([$copyId], $this->getDeletedIds());
		self::assertSame([], $this->getNotifierJobs());
	}

	public function testConditionDeletesEveryMatch(): void
	{
		Item::markForDeletion(['uri-id' => [2, 3], 'uid' => self::UID]);

		self::assertSame([2, 3, 8, 9], $this->getDeletedIds());
	}

	public function testWithoutPriorityNobodyIsNotified(): void
	{
		self::assertTrue(Item::markForDeletionById(3, 0));

		self::assertSame([3, 9], $this->getDeletedIds());
		self::assertSame([], $this->getNotifierJobs());
	}

	public function testPublicCopyIsDeletedWithoutTheUserItem(): void
	{
		self::assertTrue(Item::markForDeletionById(9));

		self::assertSame([9], $this->getDeletedIds());
		self::assertSame([], $this->getNotifierJobs());
		self::assertSame(['nodeinfo_total_posts' => -1], $this->getNodeinfoChanges());
	}

	public function testForeignThreadIsHiddenAndNotAnnounced(): void
	{
		DBA::update('post-user', ['origin' => false], ['id' => 3]);

		self::assertTrue(Item::markForDeletionById(3));

		self::assertSame([3, 9], $this->getDeletedIds());
		self::assertTrue($this->isHiddenForUser(3));
		self::assertSame([], $this->getNotifierJobs());
		self::assertSame(['nodeinfo_total_posts' => -1], $this->getNodeinfoChanges());
	}

	public function testDeleteForUserRemovesOwnItem(): void
	{
		DI::postDeletion()->deleteForUser(['id' => 3], self::UID);

		self::assertSame([3, 9], $this->getDeletedIds());
		self::assertTrue($this->isHiddenForUser(3));
	}

	public function testDeleteForUserOnlyHidesPublicCopy(): void
	{
		DI::postDeletion()->deleteForUser(['id' => 13], self::UID);

		self::assertSame([], $this->getDeletedIds());
		self::assertTrue($this->isHiddenForUser(7));
	}

	public function testDeleteForUserIgnoresOtherUsers(): void
	{
		DI::postDeletion()->deleteForUser(['id' => 3], self::OTHER_UID);
		DI::postDeletion()->deleteForUser(['id' => 3], 0);

		self::assertSame([], $this->getDeletedIds());
		self::assertFalse($this->isHiddenForUser(3));
	}

	public function testDefaultPriorityIsHigh(): void
	{
		Item::markForDeletionById(3);

		self::assertTrue(DBA::exists('workerqueue', ['command' => 'Notifier', 'priority' => Worker::PRIORITY_HIGH]));
	}
}
