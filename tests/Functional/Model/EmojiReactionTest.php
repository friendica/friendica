<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Model;

use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\Counts;
use Friendica\Model\Verb;
use Friendica\Test\FixtureTestCase;

/**
 * The collation of the "body" field treats different emojis as equal, so these tests need a real database.
 */
class EmojiReactionTest extends FixtureTestCase
{
	// post-user 1 of user 42 in api.fixture.php
	private const UID     = 42;
	private const ITEM_ID = 1;
	private const URI_ID  = 1;

	protected function setUp(): void
	{
		parent::setUp();

		// Verbs that were created in a previous test are gone after its rollback, but are still cached
		Verb::$verbs = [];
	}

	private function getReactions(): array
	{
		return array_column(Counts::getReactions(self::URI_ID, self::UID), 'count', 'name');
	}

	public function testDifferentEmojisAreDistinct(): void
	{
		self::assertTrue(Item::performActivity(self::ITEM_ID, 'react', self::UID, null, null, null, null, '🔥'));
		self::assertTrue(Item::performActivity(self::ITEM_ID, 'react', self::UID, null, null, null, null, '😂'));

		self::assertSame(['🔥' => 1, '😂' => 1], $this->getReactions());
	}

	public function testUnreactKeepsOtherEmojis(): void
	{
		Item::performActivity(self::ITEM_ID, 'react', self::UID, null, null, null, null, '🔥');
		Item::performActivity(self::ITEM_ID, 'react', self::UID, null, null, null, null, '😂');

		Item::performActivity(self::ITEM_ID, 'unreact', self::UID, null, null, null, null, '🔥');
		self::assertSame(['😂' => 1], $this->getReactions());

		// Removing an emoji that was never used must not remove anything
		Item::performActivity(self::ITEM_ID, 'unreact', self::UID, null, null, null, null, '🎉');
		self::assertSame(['😂' => 1], $this->getReactions());
	}

	public function testReactionIsNotFavourite(): void
	{
		Item::performActivity(self::ITEM_ID, 'react', self::UID, null, null, null, null, '👍');

		self::assertTrue(Post::exists(['thr-parent-id' => self::URI_ID, 'author-id' => 48, 'gravity' => Item::GRAVITY_ACTIVITY, 'deleted' => false]));

		$status = DI::mstdnStatus()->createFromUriId(self::URI_ID, self::UID, false)->toArray();
		self::assertFalse($status['favourited']);
		self::assertSame(0, $status['favourites_count']);
	}
}
