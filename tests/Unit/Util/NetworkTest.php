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
			// Cases the string based implementation could not handle, see friendica/friendica#16151
			'tracking parameter directly before a fragment' => [
				'url'    => 'https://example.com/article?id=1&utm_source=newsletter#comments',
				'expect' => 'https://example.com/article?id=1#comments',
			],
			'only a tracking parameter, followed by a fragment' => [
				'url'    => 'https://example.com/article?utm_source=newsletter#comments',
				'expect' => 'https://example.com/article#comments',
			],
			'tracking name appears inside another parameter value' => [
				'url'    => 'https://example.com/out?url=https%3A%2F%2Fshop.example%2F%3Futm_source%3Dad&utm_source=newsletter',
				'expect' => 'https://example.com/out?url=https%3A%2F%2Fshop.example%2F%3Futm_source%3Dad',
			],
			'encoded reserved characters in a kept value are preserved' => [
				'url'    => 'https://example.com/search?q=c%2B%2B&utm_source=newsletter',
				'expect' => 'https://example.com/search?q=c%2B%2B',
			],
			'kept parameter without a value' => [
				'url'    => 'https://example.com/article?draft&utm_source=newsletter',
				'expect' => 'https://example.com/article?draft=',
			],
			'tracking parameter name is matched case sensitively' => [
				'url'    => 'https://example.com/article?UTM_SOURCE=newsletter&id=1',
				'expect' => 'https://example.com/article?UTM_SOURCE=newsletter&id=1',
			],
			'input that is not a url is returned unchanged' => [
				'url'    => 'this is not a url',
				'expect' => 'this is not a url',
			],
			'input that is not a url but has a query string is returned unchanged' => [
				'url'    => 'this is not a url?test',
				'expect' => 'this is not a url?test',
			],
			'malformed uri is returned unchanged' => [
				'url'    => ':/test/it?query',
				'expect' => ':/test/it?query',
			],
		];
	}

	#[DataProvider('provideStripTrackingQueryParamsTestData')]
	public function testStripTrackingQueryParams(string $url, string $expect): void
	{
		self::assertSame($expect, Network::stripTrackingQueryParams($url));
	}

	/**
	 * @return array<string, array{0: bool, 1: string, 2: string}>
	 */
	public static function dataAllowedInternalHost(): array
	{
		return [
			// no address configured
			'empty list'             => [false, 'internal.example', ''],
			'blank entries only'     => [false, 'internal.example', ' , , '],
			'empty host, empty list' => [false, '', ''],

			// one address
			'single exact match'        => [true, 'internal.example', 'internal.example'],
			'single case insensitive'   => [true, 'Internal.Example', 'internal.example'],
			'single no match'           => [false, 'evil.example', 'internal.example'],
			'single padded with spaces' => [true, 'internal.example', '   internal.example   '],
			'single wildcard match'     => [true, 'foo.internal.example', '*.internal.example'],
			'single wildcard no match'  => [false, 'internal.example.org', '*.internal.example'],

			// two addresses
			'two, first matches'         => [true, 'a.example', 'a.example, b.example'],
			'two, second matches'        => [true, 'b.example', 'a.example, b.example'],
			'two, none matches'          => [false, 'c.example', 'a.example, b.example'],
			'two, no spaces after comma' => [true, 'b.example', 'a.example,b.example'],

			// three / four addresses
			'four, middle matches'      => [true, 'c.example', 'a.example, b.example, c.example, d.example'],
			'four, last matches'        => [true, 'd.example', 'a.example, b.example, c.example, d.example'],
			'four, none matches'        => [false, 'x.example', 'a.example, b.example, c.example, d.example'],
			'four, wildcard entry hits' => [true, 'db.internal', 'a.example, *.internal, c.example, d.example'],
			'four, extra commas'        => [true, 'c.example', 'a.example,, b.example , ,c.example,d.example'],
		];
	}

	#[DataProvider('dataAllowedInternalHost')]
	public function testIsAllowedInternalHost(bool $expected, string $host, string $allowedList): void
	{
		self::assertSame($expected, Network::isAllowedInternalHost($host, $allowedList));
	}
}
