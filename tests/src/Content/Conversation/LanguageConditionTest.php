<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Content\Conversation;

use Friendica\Content\Conversation\LanguageCondition;
use PHPUnit\Framework\TestCase;

class LanguageConditionTest extends TestCase
{
	public function testTimelineConditionWithoutLanguages(): void
	{
		self::assertSame([], (new LanguageCondition())->getTimelineCondition([]));
	}

	public function testTimelineConditionMatchesFirstJsonKeyOrNull(): void
	{
		self::assertSame(
			['(`language` LIKE ? OR `language` LIKE ? OR `language` IS NULL)', '{"de":%', '{"en":%'],
			(new LanguageCondition())->getTimelineCondition(['de', 'en']),
		);
	}

	public function testChannelConditionWithoutLanguages(): void
	{
		self::assertSame([], (new LanguageCondition())->getChannelCondition([]));
	}

	public function testChannelConditionUsesPlainCodesAndAlwaysAllowsUndetermined(): void
	{
		self::assertSame(
			['language' => ['de', 'en', 'un']],
			(new LanguageCondition())->getChannelCondition(['de', 'en']),
		);
	}

	public function testChannelConditionDoesNotDuplicateUndetermined(): void
	{
		self::assertSame(
			['language' => ['un', 'de']],
			(new LanguageCondition())->getChannelCondition(['un', 'de']),
		);
	}
}
