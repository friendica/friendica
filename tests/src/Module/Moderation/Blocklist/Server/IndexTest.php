<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Moderation\Blocklist\Server;

use Friendica\Module\Moderation\Blocklist\Server\Index;
use Friendica\Test\MockedTestCase;

class IndexTest extends MockedTestCase
{
	#[\PHPUnit\Framework\Attributes\DataProvider('filterEntriesBySearchProvider')]
	public function testFilterEntriesBySearch(array $entries, string $search, array $expectedDomains): void
	{
		$reflection = new \ReflectionClass(Index::class);
		$method     = $reflection->getMethod('filterEntriesBySearch');

		$result  = $method->invoke(null, $entries, $search);
		$domains = array_values(array_map(static fn (array $entry): string => $entry['domain'], $result));

		self::assertSame($expectedDomains, $domains);
	}

	public static function filterEntriesBySearchProvider(): array
	{
		$entries = [
			['domain' => 'spam.example', 'reason' => 'Known Spam Source'],
			['domain' => 'normal.example', 'reason' => 'No issue'],
			['domain' => 'federation.tld', 'reason' => 'Violation of policy'],
		];

		return [
			'empty search returns all entries' => [
				$entries,
				'',
				['spam.example', 'normal.example', 'federation.tld'],
			],
			'domain contains match' => [
				$entries,
				'spam',
				['spam.example'],
			],
			'reason contains match' => [
				$entries,
				'policy',
				['federation.tld'],
			],
			'case-insensitive match' => [
				$entries,
				'KNOWN',
				['spam.example'],
			],
			'no match returns empty list' => [
				$entries,
				'not-present',
				[],
			],
		];
	}
}
