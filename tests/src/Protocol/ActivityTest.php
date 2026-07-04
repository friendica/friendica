<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Protocol;

use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Test\MockedTestCase;

class ActivityTest extends MockedTestCase
{
	public static function dataMatch()
	{
		return [
			'empty' => [
				'haystack' => '',
				'needle'   => '',
				'assert'   => true,
			],
			'simple' => [
				'haystack' => Activity\ObjectType::TAGTERM,
				'needle'   => Activity\ObjectType::TAGTERM,
				'assert'   => true,
			],
			'withNamespace' => [
				'haystack' => 'tagterm',
				'needle'   => ActivityNamespace::ACTIVITY_SCHEMA . Activity\ObjectType::TAGTERM,
				'assert'   => true,
			],
			'invalidSimple' => [
				'haystack' => 'tagterm',
				'needle'   => '',
				'assert'   => false,
			],
			'invalidWithOutNamespace' => [
				'haystack' => 'tagterm',
				'needle'   => Activity\ObjectType::TAGTERM,
				'assert'   => false,
			],
			'withSubPath' => [
				'haystack' => 'tagterm',
				'needle'   => ActivityNamespace::ACTIVITY_SCHEMA . '/bla/' . Activity\ObjectType::TAGTERM,
				'assert'   => true,
			],
		];
	}

	/**
	 * Test the different, possible matchings
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataMatch')]
	public function testMatch(string $haystack, string $needle, bool $assert): void
	{
		$activity = new Activity();

		self::assertEquals($assert, $activity->match($haystack, $needle));
	}

	public function testIsHidden(): void
	{
		$activity = new Activity();

		self::assertTrue($activity->isHidden(Activity::LIKE));
		self::assertFalse($activity->isHidden(Activity\ObjectType::BOOKMARK));
	}
}
