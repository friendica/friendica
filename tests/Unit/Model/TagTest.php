<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Model;

use Friendica\Model\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
	/**
	 *
	 */
	public function testGetFromBody(): void
	{
		$body = '![url=https://pirati.ca/profile/test1]Testgruppe 1b[/url] Test, please ignore';

		$tags = Tag::getFromBody($body);

		$expected = [
			[
				'![url=https://pirati.ca/profile/test1]Testgruppe 1b[/url]',
				'!',
				'https://pirati.ca/profile/test1',
				'Testgruppe 1b',
			],
		];

		self::assertEquals($expected, $tags);
	}
}
