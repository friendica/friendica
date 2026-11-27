<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Protocol\ActivityPub;

use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Protocol\ActivityPub\Processor;
use Friendica\Protocol\ActivityPub\Receiver;
use Friendica\Protocol\Relay;
use Friendica\Test\FixtureTestCase;

class ProcessorTest extends FixtureTestCase
{
	private const URI = 'https://remote.example/objects/1';

	private const GUID = 'unsolicited-test-guid';

	// Public contact without followers in the fixture
	private const AUTHOR_ID = 43;

	public function testUnsolicitedMessageKeepsItemUriInUseByParallelInsert(): void
	{
		DI::config()->set('system', 'relay_scope', Relay::SCOPE_NONE);

		// A parallel Item::insert has already created the item-uri and stored media for it
		$uriId = ItemURI::insert(['uri' => self::URI, 'guid' => self::GUID]);
		DBA::insert('post-media', ['uri-id' => $uriId, 'url' => 'https://remote.example/a.png', 'type' => PostMedia::TYPE_IMAGE]);

		Processor::postItem(
			['receiver' => [0 => 0], 'completion-mode' => Receiver::COMPLETION_NONE],
			[
				'uri-id'          => $uriId,
				'uri'             => self::URI,
				'guid'            => self::GUID,
				'private'         => Item::PUBLIC,
				'gravity'         => Item::GRAVITY_PARENT,
				'author-id'       => self::AUTHOR_ID,
				'title'           => '',
				'body'            => 'unsolicited',
				'content-warning' => '',
			],
		);

		self::assertTrue(ItemURI::exists($uriId));
		self::assertTrue(DBA::exists('post-media', ['uri-id' => $uriId]));
	}

	public function testLocalPostIsNotUpdatedFromOutside(): void
	{
		$condition = ['uri-id' => 20001];
		$fields    = ['title', 'body'];
		$before    = DBA::selectFirst('post-content', $fields, $condition);

		// The author matches, so only the check for local posts rejects this update
		Processor::updateItem([
			'id'                    => 'https://friendica.local/objects/1',
			'author'                => 'https://friendica.local/profile/othercontact',
			'actor'                 => 'https://friendica.local/profile/othercontact',
			'object_type'           => 'as:Note',
			'updated'               => '2026-11-27T12:00:00Z',
			'name'                  => '',
			'content'               => 'defaced',
			'source'                => null,
			'tags'                  => [],
			'attachments'           => [],
			'emojis'                => [],
			'languages'             => [],
			'transmitted-languages' => [],
			'location'              => null,
			'generator'             => null,
		]);

		self::assertSame($before, DBA::selectFirst('post-content', $fields, $condition));
	}
}
