<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Factory\Api\Mastodon;

use Friendica\Content\Smilies;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;

class EmojiTest extends FixtureTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		DI::config()->set('system', 'no_smilies', false);
	}

	public function testBuiltInCollection(): void
	{
		$emoji      = DI::mstdnEmoji();
		$collection = $emoji->createCollectionFromSmilies(Smilies::getList())->getArrayCopy(true);
		foreach ($collection as $item) {
			$this->assertTrue(preg_match('(/images/.*)', (string) $item['url']) === 1, $item['url']);
		}
	}
}
