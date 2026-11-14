<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Content\Conversation;

use Friendica\Content\Conversation\PostInteractions;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;
use Friendica\Test\FixtureTestCase;
use InvalidArgumentException;
use ReflectionMethod;

/**
 * The conditions need the verb ids, which are stored in the database.
 */
class PostInteractionsConditionTest extends FixtureTestCase
{
	private PostInteractions $interactions;

	protected function setUp(): void
	{
		parent::setUp();

		$pConfig = $this->createStub(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturn(false);

		$this->interactions = new PostInteractions($this->createStub(L10n::class), $pConfig);
	}

	private function condition(string $type, int $uriId = 42, int $uid = 0): array
	{
		return (new ReflectionMethod(PostInteractions::class, 'getActivityCondition'))->invoke($this->interactions, $type, $uriId, $uid);
	}

	/**
	 * The condition is merged into an SQL string followed by its parameters
	 */
	private function parameters(string $type, int $uriId = 42): array
	{
		return array_slice($this->condition($type, $uriId), 1);
	}

	public function testConditionPerType(): void
	{
		$announce   = Verb::getID(Activity::ANNOUNCE);
		$like       = Verb::getID(Activity::LIKE);
		$dislike    = Verb::getID(Activity::DISLIKE);
		$emojiReact = Verb::getID(Activity::EMOJIREACT);

		self::assertContains($announce, $this->parameters(PostInteractions::RESHARES));
		self::assertNotContains($like, $this->parameters(PostInteractions::RESHARES));

		self::assertContains($like, $this->parameters(PostInteractions::LIKES));
		self::assertNotContains($dislike, $this->parameters(PostInteractions::LIKES));

		self::assertContains($dislike, $this->parameters(PostInteractions::DISLIKES));
		self::assertNotContains($like, $this->parameters(PostInteractions::DISLIKES));

		foreach ([$emojiReact, $like, $dislike] as $vid) {
			self::assertContains($vid, $this->parameters(PostInteractions::REACTIONS));
		}
		self::assertNotContains($announce, $this->parameters(PostInteractions::REACTIONS));
	}

	public function testConditionIsRestrictedToTheActivitiesOfThePost(): void
	{
		self::assertContains(42, $this->parameters(PostInteractions::LIKES, 42));
		self::assertNotContains(43, $this->parameters(PostInteractions::LIKES, 42));
	}

	public function testUnknownTypeIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->condition('unknown');
	}
}
