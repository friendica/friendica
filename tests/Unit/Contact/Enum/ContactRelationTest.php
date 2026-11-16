<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Contact\Enum;

use Friendica\Contact\Enum\ContactRelation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContactRelationTest extends TestCase
{
	/**
	 * @return array<string, array{relation: ContactRelation, isFollower: bool, isSharing: bool}>
	 */
	public static function provideDirections(): array
	{
		return [
			'nothing'  => ['relation' => ContactRelation::Nothing, 'isFollower' => false, 'isSharing' => false],
			'follower' => ['relation' => ContactRelation::Follower, 'isFollower' => true, 'isSharing' => false],
			'sharing'  => ['relation' => ContactRelation::Sharing, 'isFollower' => false, 'isSharing' => true],
			'friend'   => ['relation' => ContactRelation::Friend, 'isFollower' => true, 'isSharing' => true],
			'self'     => ['relation' => ContactRelation::Self, 'isFollower' => false, 'isSharing' => false],
		];
	}

	#[DataProvider('provideDirections')]
	public function testDirections(ContactRelation $relation, bool $isFollower, bool $isSharing): void
	{
		self::assertSame($isFollower, $relation->isFollower());
		self::assertSame($isSharing, $relation->isSharing());
	}

	public function testChangingOneDirectionKeepsTheOther(): void
	{
		self::assertSame(ContactRelation::Friend, ContactRelation::Sharing->withFollower());
		self::assertSame(ContactRelation::Sharing, ContactRelation::Friend->withoutFollower());
		self::assertSame(ContactRelation::Friend, ContactRelation::Follower->withSharing());
		self::assertSame(ContactRelation::Follower, ContactRelation::Friend->withoutSharing());
	}
}
