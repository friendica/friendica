<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Protocol\ActivityPub;

use Friendica\Protocol\ActivityPub\Receiver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReceiverTest extends TestCase
{
	public static function dataQuoteRequestInstrument(): array
	{
		return [
			'embedded note' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/note/1', '@type' => 'as:Note']],
				'expected' => 'https://example.test/note/1',
			],
			'embedded article' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/article/1', '@type' => 'as:Article']],
				'expected' => 'https://example.test/article/1',
			],
			'link only' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/note/1']],
				'expected' => 'https://example.test/note/1',
			],
			'application and note' => [
				'activity' => ['as:instrument' => [
					['@id' => 'https://example.test/app', '@type' => 'as:Application'],
					['@id' => 'https://example.test/note/1', '@type' => 'as:Note'],
				]],
				'expected' => 'https://example.test/note/1',
			],
			'application only' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/app', '@type' => 'as:Application']],
				'expected' => null,
			],
			'no instrument' => [
				'activity' => [],
				'expected' => null,
			],
		];
	}

	#[DataProvider('dataQuoteRequestInstrument')]
	public function testFetchQuoteRequestInstrument(array $activity, ?string $expected): void
	{
		$method = new \ReflectionMethod(Receiver::class, 'fetchQuoteRequestInstrument');

		self::assertSame($expected, $method->invoke(null, $activity));
	}
}
