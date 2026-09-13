<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Model\Post;

use Friendica\Model\Post\MediaExif;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MediaExifTest extends TestCase
{
	public static function provideOrientations(): array
	{
		return [
			'missing'        => [[], null],
			'topLeft'        => [['Orientation' => 1], 1],
			'leftBottom'     => [['Orientation' => 8], 8],
			'numericString'  => [['Orientation' => '6'], 6],
			'zero'           => [['Orientation' => 0], null],
			'negative'       => [['Orientation' => -1], null],
			'aboveExifRange' => [['Orientation' => 9], null],
			// Reported in https://github.com/friendica/friendica/issues/15817
			'outOfColumnRange' => [['Orientation' => 65537], null],
			'nonNumeric'       => [['Orientation' => 'top-left'], null],
			'null'             => [['Orientation' => null], null],
		];
	}

	#[DataProvider('provideOrientations')]
	public function testOrientationOutsideTheExifRangeIsDiscarded(array $exif, ?int $expected): void
	{
		// The method is an implementation detail of MediaExif::insert(), which needs a database
		$method = new ReflectionMethod(MediaExif::class, 'getOrientation');

		self::assertSame($expected, $method->invoke(null, $exif));
	}
}
