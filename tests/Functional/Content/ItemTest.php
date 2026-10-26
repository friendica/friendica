<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Functional\Content;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Database\PostUpdate;
use Friendica\DI;
use Friendica\Model\Circle;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\Strings;

class ItemTest extends FixtureTestCase
{
	private const UID = 42;

	// Self contact of the user in the fixture
	private const OWNER_ID = 48;

	// Followed contact of the user in the fixture that isn't a group
	private const FRIEND_ID = 44;

	protected function setUp(): void
	{
		parent::setUp();

		// Prevents the legacy contact lookup that would probe the contacts
		DI::keyValue()->set('post_update_version', PostUpdate::VERSION);
	}

	/**
	 * Creates a group that the user follows
	 *
	 * @return array User contact and public contact id of the group
	 */
	private function createGroup(string $nick, bool $private = false, bool $pending = false): array
	{
		$url   = 'https://example.org/c/' . $nick;
		$uriId = ItemURI::insert(['uri' => $url, 'guid' => $nick]);

		$fields = [
			'uri-id'           => $uriId,
			'name'             => $nick,
			'nick'             => $nick,
			'url'              => $url,
			'nurl'             => Strings::normaliseLink($url),
			'network'          => Protocol::ACTIVITYPUB,
			'contact-type'     => Contact::TYPE_COMMUNITY,
			'manually-approve' => $private,
			'blocked'          => false,
		];

		DBA::insert('contact', array_merge($fields, ['uid' => 0, 'rel' => Contact::NOTHING, 'pending' => false]));
		$pcid = DBA::lastInsertId();

		DBA::insert('contact', array_merge($fields, ['uid' => self::UID, 'rel' => Contact::SHARING, 'prv' => $private, 'pending' => $pending]));
		$ucid = DBA::lastInsertId();

		return ['contact' => Contact::getById($ucid), 'pcid' => $pcid];
	}

	private function getItem(string $allow_cid, int $gravity = Item::GRAVITY_PARENT): array
	{
		return [
			'uid'       => self::UID,
			'network'   => Protocol::DFRN,
			'gravity'   => $gravity,
			'body'      => 'Post without mentions',
			'private'   => Item::PRIVATE,
			'owner-id'  => self::OWNER_ID,
			'allow_cid' => $allow_cid,
			'allow_gid' => '',
			'deny_cid'  => '',
			'deny_gid'  => '',
		];
	}

	private function assertUnchanged(array $item, array $expanded): void
	{
		self::assertSame($item['private'], $expanded['private']);
		self::assertSame($item['owner-id'], $expanded['owner-id']);
		self::assertSame($item['allow_cid'], $expanded['allow_cid']);
		self::assertArrayNotHasKey('audience', $expanded);
		self::assertSame('', $expanded['inform']);
	}

	public function testGroupPostToPublicGroup(): void
	{
		$group = $this->createGroup('publicgroup');
		$item  = $this->getItem('<' . $group['contact']['id'] . '>');

		$expanded = DI::contentItem()->expandTags($item, false, true);

		self::assertSame(Item::UNLISTED, $expanded['private']);
		self::assertEquals($group['pcid'], $expanded['owner-id']);
		self::assertSame($group['contact']['url'], $expanded['audience']);
		self::assertSame('cid:' . $group['contact']['id'], $expanded['inform']);
		self::assertSame('', $expanded['allow_cid']);
		self::assertSame('', $expanded['allow_gid']);
	}

	public function testGroupPostToPrivateGroup(): void
	{
		$group = $this->createGroup('privategroup', true);
		$item  = $this->getItem('<' . $group['contact']['id'] . '>');

		$expanded = DI::contentItem()->expandTags($item, false, true);

		self::assertSame(Item::PRIVATE, $expanded['private']);
		self::assertEquals($group['pcid'], $expanded['owner-id']);
		self::assertSame($group['contact']['url'], $expanded['audience']);
		self::assertSame('<' . $group['contact']['id'] . '>', $expanded['allow_cid']);
		self::assertSame('<' . Circle::getIdForGroup($group['contact']['id']) . '>', $expanded['allow_gid']);
	}

	/**
	 * Only posts from the group editor are converted, a post that is just addressed to a single group via the ACL stays unchanged
	 */
	public function testPostAddressedToGroupWithoutGroupEditor(): void
	{
		$group = $this->createGroup('publicgroup');
		$item  = $this->getItem('<' . $group['contact']['id'] . '>');

		$this->assertUnchanged($item, DI::contentItem()->expandTags($item));
	}

	public function testGroupPostToPendingGroup(): void
	{
		$group = $this->createGroup('pendinggroup', false, true);
		$item  = $this->getItem('<' . $group['contact']['id'] . '>');

		$this->assertUnchanged($item, DI::contentItem()->expandTags($item, false, true));
	}

	public function testGroupPostToContactThatIsNoGroup(): void
	{
		$item = $this->getItem('<' . self::FRIEND_ID . '>');

		$this->assertUnchanged($item, DI::contentItem()->expandTags($item, false, true));
	}

	public function testGroupPostWithAdditionalReceivers(): void
	{
		$group = $this->createGroup('publicgroup');
		$item  = $this->getItem('<' . $group['contact']['id'] . '><' . self::FRIEND_ID . '>');

		$this->assertUnchanged($item, DI::contentItem()->expandTags($item, false, true));
	}

	public function testGroupPostComment(): void
	{
		$group = $this->createGroup('publicgroup');
		$item  = $this->getItem('<' . $group['contact']['id'] . '>', Item::GRAVITY_COMMENT);

		$this->assertUnchanged($item, DI::contentItem()->expandTags($item, false, true));
	}
}
