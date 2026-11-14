<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Test\FixtureTestCase;

class ItemTest extends FixtureTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);
	}

	public function testPrepareBodyProvidesAllTagLists(): void
	{
		DBA::insert('tag', ['name' => 'friendica', 'url' => '']);
		DBA::insert('post-tag', ['uri-id' => 1, 'type' => Tag::HASHTAG, 'tid' => DBA::lastInsertId()]);
		DBA::insert('tag', ['name' => 'bob', 'url' => 'https://remote.example/profile/bob']);
		DBA::insert('post-tag', ['uri-id' => 1, 'type' => Tag::IMPLICIT_MENTION, 'tid' => DBA::lastInsertId()]);

		$item = Post::selectFirst([], ['uri-id' => 1, 'uid' => 42]);
		Item::prepareBody($item, true);

		$expected = Tag::populateFromItem($item);
		self::assertCount(1, $expected['hashtags']);
		self::assertCount(1, $expected['implicit_mentions']);

		foreach ($expected as $list => $tags) {
			self::assertSame($tags, $item[$list] ?? null, $list . ' must be set by prepareBody() so the template does not query the tags again.');
		}
	}
}
