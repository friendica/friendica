<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Content\Conversation;

use Friendica\Content\Conversation\PostInteractionCounts;
use Friendica\Content\Conversation\Repository\InteractionCounts;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Test\FixtureTestCase;

class PostInteractionCountsTest extends FixtureTestCase
{
	// post-user 1 of user 42 in api.fixture.php
	private const UID     = 42;
	private const ITEM_ID = 1;
	private const URI_ID  = 1;

	private PostInteractionCounts $counts;

	protected function setUp(): void
	{
		parent::setUp();

		$this->counts = new PostInteractionCounts(new InteractionCounts(DI::dba()));
	}

	public function testPostWithoutInteractionsHasNoCounts(): void
	{
		self::assertSame(['reshares' => 0, 'likes' => 0, 'dislikes' => 0], $this->counts->getTotals(self::URI_ID, self::URI_ID, self::UID));
		self::assertSame([], $this->counts->getEmojis([self::URI_ID], self::UID));
		self::assertSame([], $this->counts->getQuoteShares([self::URI_ID], self::UID));
	}

	public function testPublicLikeIsCountedForEveryViewer(): void
	{
		self::assertTrue(Item::performActivity(self::ITEM_ID, 'like', self::UID));

		self::assertSame(1, $this->counts->getTotals(self::URI_ID, self::URI_ID, self::UID)['likes']);
		self::assertSame(1, $this->counts->getTotals(self::URI_ID, self::URI_ID, 0)['likes']);
	}

	public function testPublicReactionIsMarkedAsOwnForTheAuthor(): void
	{
		self::assertTrue(Item::performActivity(self::ITEM_ID, 'react', self::UID, null, null, null, null, '🔥'));

		$own = $this->counts->getEmojis([self::URI_ID], self::UID)[self::URI_ID]['🔥'];
		self::assertSame(1, $own['total']);
		self::assertTrue($own['self']);

		$other = $this->counts->getEmojis([self::URI_ID], 0)[self::URI_ID]['🔥'];
		self::assertSame(1, $other['total']);
		self::assertFalse($other['self']);
	}

	public function testPrivateReactionIsAddedForItsAuthor(): void
	{
		self::assertTrue(Item::performActivity(self::ITEM_ID, 'react', self::UID, '<43>', null, null, null, '🔥'));

		$own = $this->counts->getEmojis([self::URI_ID], self::UID)[self::URI_ID]['🔥'] ?? null;
		self::assertNotNull($own);
		self::assertSame(1, $own['total']);
		self::assertTrue($own['self']);
	}

	public function testRestrictingToTargetPostsLeavesOtherPostsOut(): void
	{
		self::assertTrue(Item::performActivity(self::ITEM_ID, 'react', self::UID, null, null, null, null, '🔥'));

		self::assertSame([], $this->counts->getEmojis([self::URI_ID], self::UID, [2]));
		self::assertArrayHasKey('🔥', $this->counts->getEmojis([self::URI_ID], self::UID, [self::URI_ID])[self::URI_ID]);
	}
}
