<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Model;

use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Test\FixtureTestCase;

/**
 * Post visibility when a remote contact has blocked the local user.
 *
 * `user-contact`.`blocked`    means "our user blocked this contact"  - a preference the user expressed.
 * `user-contact`.`is-blocked` means "this contact blocked our user"  - a preference the user did not express.
 *
 * The two must not have the same effect. A block by the author of a thread must not block
 * access to posts written by anybody else, including the user's own.
 *
 * Fixture layout used here (tests/Fixtures/api.fixture.php):
 *
 *   uid 42       the local user
 *   contact 43   remote contact; author and owner of the thread rooted at uri-id 1
 *   contact 45   remote contact; author of the reply at uri-id 4, whose owner is still 43
 *
 * Contact 43 plays the blocker throughout.
 */
class PostTest extends FixtureTestCase
{
	private const UID = 42;

	/** Author and owner of the thread root; the contact that blocks our user. */
	private const BLOCKER_CID = 43;

	/** Author of the reply at REPLY_URI_ID; has not blocked anybody. */
	private const THIRD_PARTY_CID = 45;

	/** Thread root, authored and owned by BLOCKER_CID. */
	private const ROOT_URI_ID = 1;

	/** Reply authored by THIRD_PARTY_CID, but owned by BLOCKER_CID. */
	private const REPLY_URI_ID = 4;

	private const FIELDS = ['uri-id', 'author-id', 'owner-id'];

	/**
	 * The contact blocked our user (an inbound ActivityPub Block).
	 */
	private function contactBlocksUser(int $cid): void
	{
		DBA::insert('user-contact', ['uid' => self::UID, 'cid' => $cid, 'is-blocked' => true]);
	}

	/**
	 * Our user blocked the contact.
	 */
	private function userBlocksContact(int $cid): void
	{
		DBA::insert('user-contact', ['uid' => self::UID, 'cid' => $cid, 'blocked' => true]);
	}

	/**
	 * Baseline: without any block both posts are visible, so the assertions below
	 * are about the block and not about the fixture.
	 */
	public function testWithoutAnyBlockBothPostsAreVisible(): void
	{
		$root = Post::selectFirstForUser(self::UID, self::FIELDS, ['uri-id' => self::ROOT_URI_ID, 'uid' => self::UID]);
		self::assertNotEmpty($root);
		self::assertEquals(self::BLOCKER_CID, $root['author-id']);

		$reply = Post::selectFirstForUser(self::UID, self::FIELDS, ['uri-id' => self::REPLY_URI_ID, 'uid' => self::UID]);
		self::assertNotEmpty($reply);
		self::assertEquals(self::THIRD_PARTY_CID, $reply['author-id']);
	}

	/**
	 * Unchanged behaviour, asserted so a later refactor cannot weaken it by accident:
	 * a post written by the contact that blocked us stays hidden in ordinary queries.
	 */
	public function testRemoteBlockHidesThePostsOfTheBlocker(): void
	{
		$this->contactBlocksUser(self::BLOCKER_CID);

		$root = Post::selectFirstForUser(self::UID, self::FIELDS, ['uri-id' => self::ROOT_URI_ID, 'uid' => self::UID]);

		self::assertEmpty($root);
	}

	/**
	 * The bug.
	 *
	 * The reply at REPLY_URI_ID was written by THIRD_PARTY_CID, who has blocked nobody.
	 * It is only *owned* by the blocker, because the owner of a received comment is
	 * frequently the thread owner. Applying `is-blocked` to `owner-id` therefore removes
	 * access to content written by uninvolved people - and, in a thread the user took
	 * part in, the user's own posts as well.
	 */
	public function testRemoteBlockDoesNotHideAThirdPartyReply(): void
	{
		$this->contactBlocksUser(self::BLOCKER_CID);

		$reply = Post::selectFirstForUser(self::UID, self::FIELDS, ['uri-id' => self::REPLY_URI_ID, 'uid' => self::UID]);

		self::assertNotEmpty($reply, 'A reply written by a contact who has not blocked us must stay visible');
		self::assertEquals(self::THIRD_PARTY_CID, $reply['author-id']);
	}

	/**
	 * The second half of the bug.
	 *
	 * Rendering a conversation needs the thread root even when the root's author blocked
	 * us, otherwise the whole thread collapses to "Conversation Not Found". The render
	 * layer withholds its content instead of dropping the node.
	 */
	public function testConversationFetchKeepsTheBlockersPostSoTheThreadSurvives(): void
	{
		$this->contactBlocksUser(self::BLOCKER_CID);

		$root = Post::selectFirstForConversation(self::UID, self::FIELDS, ['uri-id' => self::ROOT_URI_ID, 'uid' => self::UID]);

		self::assertNotEmpty($root, 'The thread root must be fetchable so the conversation can be rendered');
		self::assertEquals(self::BLOCKER_CID, $root['author-id']);
	}

	/**
	 * The escape hatch must not weaken a block the user actually asked for.
	 */
	public function testConversationFetchStillHonoursTheUsersOwnBlock(): void
	{
		$this->userBlocksContact(self::BLOCKER_CID);

		$root = Post::selectFirstForConversation(self::UID, self::FIELDS, ['uri-id' => self::ROOT_URI_ID, 'uid' => self::UID]);

		self::assertEmpty($root, 'A block set by the user themselves must hide the post in every query');
	}
}
