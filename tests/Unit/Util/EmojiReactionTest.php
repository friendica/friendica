<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util;

use Friendica\Util\EmojiReaction;
use PHPUnit\Framework\TestCase;

class EmojiReactionTest extends TestCase
{
	public static function dataIsReaction(): array
	{
		return [
			// Format of already stored reactions
			'single codepoint' => [
				'expected' => true,
				'body'     => '🔥',
			],
			'skin tone' => [
				'expected' => true,
				'body'     => '👍🏽',
			],
			'variation selector' => [
				'expected' => true,
				'body'     => '❤️',
			],
			'flag' => [
				'expected' => true,
				'body'     => '🇩🇪',
			],
			'tag sequence' => [
				'expected' => true,
				'body'     => '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
			],
			'keycap' => [
				'expected' => true,
				'body'     => '1️⃣',
			],
			'zwj' => [
				'expected' => true,
				'body'     => '👨‍👩‍👧‍👦',
			],
			'zwj+skin tones' => [
				'expected' => true,
				'body'     => '👩🏽‍❤️‍💋‍👨🏿',
			],
			'two emojis' => [
				'expected' => false,
				'body'     => '👍👍',
			],
			'two flags' => [
				'expected' => false,
				'body'     => '🇩🇪🇩🇪',
			],
			'custom emoji' => [
				'expected' => false,
				'body'     => ':blobcat:',
			],
			'custom emoji BBCode' => [
				'expected' => false,
				'body'     => '[emoji=https://example.com/blobcat.png]:blobcat:[/emoji]',
			],
			'letter' => [
				'expected' => false,
				'body'     => 'a',
			],
			'emoji+whitespace' => [
				'expected' => false,
				'body'     => ' 👍',
			],
			'verb' => [
				'expected' => false,
				'body'     => 'http://activitystrea.ms/schema/1.0/like',
			],
			'empty' => [
				'expected' => false,
				'body'     => '',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsReaction')]
	public function testIsReaction(bool $expected, string $body): void
	{
		$this->assertEquals($expected, EmojiReaction::isReaction($body));
	}

	public static function dataIsPositiveReaction(): array
	{
		return [
			'thumbs up' => [
				'expected' => true,
				'reaction' => '👍',
			],
			'thumbs up+skin tone' => [
				'expected' => true,
				'reaction' => '👍🏿',
			],
			'heart' => [
				'expected' => true,
				'reaction' => '❤',
			],
			'heart+variation selector' => [
				'expected' => true,
				'reaction' => '❤️',
			],
			'thumbs down' => [
				'expected' => false,
				'reaction' => '👎',
			],
			'laughing' => [
				'expected' => false,
				'reaction' => '😂',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsPositiveReaction')]
	public function testIsPositiveReaction(bool $expected, string $reaction): void
	{
		$this->assertEquals($expected, EmojiReaction::isPositiveReaction($reaction));
	}
}
