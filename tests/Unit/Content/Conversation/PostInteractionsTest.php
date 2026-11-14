<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Content\Conversation;

use Friendica\Content\Conversation\PostInteractions;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PostInteractionsTest extends TestCase
{
	private function createInteractions(bool $hideDislike): PostInteractions
	{
		$l10n = $this->createStub(L10n::class);
		$l10n->method('tt')->willReturnCallback(fn (string $singular, string $plural, float $count): string => $count == 1 ? $singular : $plural);
		$l10n->method('t')->willReturnArgument(0);

		$pConfig = $this->createStub(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturn($hideDislike);

		return new PostInteractions($l10n, $pConfig);
	}

	public function testTypesWithDislikes(): void
	{
		$interactions = $this->createInteractions(false);

		self::assertSame(
			[PostInteractions::RESHARES, PostInteractions::QUOTES, PostInteractions::LIKES, PostInteractions::REACTIONS, PostInteractions::DISLIKES],
			$interactions->getTypes(1),
		);
		self::assertTrue($interactions->isAvailable(PostInteractions::DISLIKES, 1));
	}

	public function testTypesWithHiddenDislikes(): void
	{
		$interactions = $this->createInteractions(true);

		self::assertNotContains(PostInteractions::DISLIKES, $interactions->getTypes(1));
		self::assertFalse($interactions->isAvailable(PostInteractions::DISLIKES, 1));
		self::assertTrue($interactions->isAvailable(PostInteractions::LIKES, 1));
	}

	public function testUnknownTypeIsNotAvailable(): void
	{
		self::assertFalse($this->createInteractions(false)->isAvailable('unknown', 0));
	}

	public function testLabelUsesPlural(): void
	{
		$interactions = $this->createInteractions(false);

		self::assertSame('like', $interactions->getLabel(PostInteractions::LIKES, 1));
		self::assertSame('likes', $interactions->getLabel(PostInteractions::LIKES, 2));
		self::assertSame('', $interactions->getLabel('unknown', 2));
	}

	public function testAuthorConditionWithoutViewer(): void
	{
		$condition = (new ReflectionMethod(PostInteractions::class, 'getAuthorCondition'))->invoke($this->createInteractions(false), 0);

		self::assertSame(['NOT `author-blocked`'], $condition);
	}

	public function testAuthorConditionLeavesOutBlockedAndIgnoredContactsOfTheViewer(): void
	{
		$condition = (new ReflectionMethod(PostInteractions::class, 'getAuthorCondition'))->invoke($this->createInteractions(false), 7);

		self::assertStringContainsString('`user-contact`', $condition[0]);
		self::assertSame(7, $condition[1]);
	}
}
