<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Content\Post\Entity;

use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Test\FixtureTestCase;
use Friendica\Test\src\Content\Post\PostMediaRowTrait;

class PostMediaTest extends FixtureTestCase
{
	use PostMediaRowTrait;

	public function testAudioVideoAndHlsArePlayable(): void
	{
		foreach ([PostMedia::TYPE_AUDIO, PostMedia::TYPE_VIDEO, PostMedia::TYPE_HLS] as $type) {
			self::assertTrue($this->createMedia(['type' => $type])->isPlayable(), 'Type ' . $type);
		}
	}

	public function testOtherTypesAreNotPlayable(): void
	{
		foreach ([PostMedia::TYPE_UNKNOWN, PostMedia::TYPE_IMAGE, PostMedia::TYPE_APPLICATION, PostMedia::TYPE_TORRENT, PostMedia::TYPE_HTML, PostMedia::TYPE_DOCUMENT] as $type) {
			self::assertFalse($this->createMedia(['type' => $type])->isPlayable(), 'Type ' . $type);
		}
	}
}
