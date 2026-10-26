<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Test\ApiTestCase;
use Friendica\Test\FixtureTestCase;

class TagSearchTest extends FixtureTestCase
{
	private const UID = ApiTestCase::SELF_USER['id'];

	protected function setUp(): void
	{
		parent::setUp();

		// The threads 1, 3 and 6 of the fixture carry the tag
		DI::dba()->insert('tag', ['name' => 'friendicatest', 'url' => '', 'type' => 0]);
		$tagId = DI::dba()->lastInsertId();

		foreach ([1, 3, 6] as $uriId) {
			DI::dba()->insert('post-tag', ['uri-id' => $uriId, 'type' => Tag::HASHTAG, 'tid' => $tagId, 'cid' => 0]);
		}
	}

	public function testAllPostsWithTheTagAreReturnedNewestFirst(): void
	{
		self::assertSame([6, 3, 1], Tag::getURIIdListByTag('friendicatest', self::UID));
	}

	public function testOnlyPostsAfterTheFirstUriIdAreReturned(): void
	{
		self::assertSame([6, 3], Tag::getURIIdListByTag('friendicatest', self::UID, 0, 100, 0, 1));
		self::assertSame([6], Tag::getURIIdListByTag('friendicatest', self::UID, 0, 100, 0, 3));
		self::assertSame([], Tag::getURIIdListByTag('friendicatest', self::UID, 0, 100, 0, 6));
	}

	public function testFirstAndLastUriIdCanBeCombined(): void
	{
		self::assertSame([3], Tag::getURIIdListByTag('friendicatest', self::UID, 0, 100, 6, 1));
	}

	public function testOtherTagsAreNotReturned(): void
	{
		self::assertSame([], Tag::getURIIdListByTag('unknowntag', self::UID, 0, 100, 0, 1));
	}
}
