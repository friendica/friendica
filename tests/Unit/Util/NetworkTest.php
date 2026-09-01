<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Util;

use Friendica\Util\Network;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NetworkTest extends TestCase
{
	public static function provideStripTrackingQueryParamsTestData(): array
	{
		return [
			'tracking parameter between two kept parameters' => [
				'url'    => 'https://example.com/article?id=1&utm_source=newsletter&page=2',
				'expect' => 'https://example.com/article?id=1&page=2',
			],
			'tracking parameter last' => [
				'url'    => 'https://example.com/article?id=1&utm_source=newsletter',
				'expect' => 'https://example.com/article?id=1',
			],
			'tracking parameter first' => [
				'url'    => 'https://example.com/article?utm_source=newsletter&page=2',
				'expect' => 'https://example.com/article?page=2',
			],
			'only a tracking parameter' => [
				'url'    => 'https://example.com/article?utm_source=newsletter',
				'expect' => 'https://example.com/article',
			],
			'two adjacent tracking parameters' => [
				'url'    => 'https://example.com/article?id=1&utm_source=newsletter&utm_medium=mail&page=2',
				'expect' => 'https://example.com/article?id=1&page=2',
			],
			'non utm tracking parameter' => [
				'url'    => 'https://example.com/article?id=1&fb_ref=abc&page=2',
				'expect' => 'https://example.com/article?id=1&page=2',
			],
			'url without tracking parameters is untouched' => [
				'url'    => 'https://example.com/article?id=1&page=2',
				'expect' => 'https://example.com/article?id=1&page=2',
			],
			'url without query string is untouched' => [
				'url'    => 'https://example.com/article',
				'expect' => 'https://example.com/article',
			],
		];
	}

	/**
	 * The returned URL is fetched by the HTTP client and stored as the link of a
	 * post, so removing a parameter must not change the remaining ones.
	 */
	#[DataProvider('provideStripTrackingQueryParamsTestData')]
	public function testStripTrackingQueryParams(string $url, string $expect): void
	{
		self::assertSame($expect, Network::stripTrackingQueryParams($url));
	}
}
