<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Moderation\Blocklist;

use Friendica\Module\Moderation\Blocklist\Contact;
use Friendica\Test\MockedTestCase;

class ContactTest extends MockedTestCase
{
	public function testBuildSearchConditionWithoutSearchReturnsOriginalCondition(): void
	{
		$baseCondition = ['uid' => 0, 'blocked' => true];

		$condition = self::callBuildSearchCondition($baseCondition, '');

		self::assertSame($baseCondition, $condition);
	}

	public function testBuildSearchConditionWithSearchAddsLikeClauseAndParameters(): void
	{
		$baseCondition = ['uid' => 0, 'blocked' => true];

		$condition = self::callBuildSearchCondition($baseCondition, 'SpamHost');

		self::assertIsArray($condition);
		self::assertArrayHasKey(0, $condition);
		self::assertStringContainsString('`addr` LIKE ?', $condition[0]);
		self::assertStringContainsString('`block_reason` LIKE ?', $condition[0]);

		$searchTerms = array_values(array_filter($condition, static fn ($value): bool => $value === '%SpamHost%'));
		self::assertCount(6, $searchTerms);
	}

	private static function callBuildSearchCondition(array $condition, string $search): array
	{
		$reflection = new \ReflectionClass(Contact::class);
		$method     = $reflection->getMethod('buildSearchCondition');

		return $method->invoke(null, $condition, $search);
	}
}
