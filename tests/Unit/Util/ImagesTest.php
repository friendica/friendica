<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Util;

use Friendica\Util\Images;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImagesTest extends TestCase
{
	/**
	 * PNG signature and IHDR chunk only: enough for getimagesizefromstring(),
	 * no pixel data, so the declared size costs nothing to construct.
	 */
	private static function pngHeader(int $width, int $height): string
	{
		$ihdr = 'IHDR' . pack('NN', $width, $height) . "\x08\x06\x00\x00\x00";

		return "\x89PNG\r\n\x1a\n" . pack('N', 13) . $ihdr . pack('N', crc32($ihdr));
	}

	public static function provideDecodeSizes(): array
	{
		return [
			// 210 megapixel JPEG seen in federation: ~840 MB as a GD truecolor bitmap
			'oversized'       => [self::pngHeader(14000, 15000), 512 * 1024 * 1024, false],
			'fits'            => [self::pngHeader(4000, 3000), 512 * 1024 * 1024, true],
			'exactly at edge' => [self::pngHeader(1024, 1024), 8 * 1024 * 1024, true],
			'one pixel over'  => [self::pngHeader(1024, 1025), 8 * 1024 * 1024, false],
			// rotating or scaling after the decode needs a second bitmap
			'no second bitmap' => [self::pngHeader(1024, 1024), 4 * 1024 * 1024, false],
			'no budget left'   => [self::pngHeader(1, 1), 0, false],
		];
	}

	#[DataProvider('provideDecodeSizes')]
	public function testFitsIntoMemoryComparesTheDecodedSizeWithTheBudget(string $data, int $availableBytes, bool $expected): void
	{
		self::assertSame($expected, Images::fitsIntoMemory($data, $availableBytes));
	}

	public function testFitsIntoMemoryLeavesUnreadableHeadersToTheDecoder(): void
	{
		self::assertTrue(Images::fitsIntoMemory('not an image', 1));
	}
}
