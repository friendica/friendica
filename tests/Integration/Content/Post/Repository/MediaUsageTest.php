<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Content\Post\Repository;

use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Content\Post\Repository\MediaUsage;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;

/** Finds the user's own posts that use a local photo or attachment, based on api.fixture.php. */
class MediaUsageTest extends FixtureTestCase
{
	private const USER_ID     = 42;
	private const OTHER_UID   = 4242;
	private const RESOURCE_ID = '0123456789abcdef0123456789abcdef';
	private const ATTACH_ID   = 4711;

	private MediaUsage $mediaUsage;

	protected function setUp(): void
	{
		parent::setUp();

		$this->mediaUsage = DI::getDice()->create(MediaUsage::class);

		// Posts 1 and 2 are authored by user 42, post 3 is only a copy in the stream of user 42.
		DI::dba()->update('post-user', ['created' => '2026-01-01 00:00:00'], ['uri-id' => 1]);
		DI::dba()->update('post-user', ['created' => '2026-02-01 00:00:00'], ['uri-id' => 2]);
		foreach ([1 => 1, 2 => 2] as $id => $uriId) {
			self::assertTrue(DI::dba()->insert('post-origin', ['id' => $id, 'uri-id' => $uriId, 'uid' => self::USER_ID, 'parent-uri-id' => 1]));
		}
	}

	private function addMedia(int $uriId, string $url, ?string $preview = null, ?int $attachId = null): void
	{
		self::assertTrue(DI::dba()->insert('post-media', [
			'uri-id'    => $uriId,
			'url'       => $url,
			'preview'   => $preview,
			'attach-id' => $attachId,
			'type'      => $attachId ? PostMedia::TYPE_DOCUMENT : PostMedia::TYPE_IMAGE,
		]));
	}

	private function photoUrl(int $scale): string
	{
		return 'https://friendica.local/photo/' . self::RESOURCE_ID . '-' . $scale . '.jpg';
	}

	public function testPhotoIsFoundByUrlAndPreviewNewestFirst(): void
	{
		$this->addMedia(1, $this->photoUrl(0));
		$this->addMedia(2, 'https://example.org/other.jpg', $this->photoUrl(1));

		$posts = $this->mediaUsage->selectPostsByPhoto(self::RESOURCE_ID, self::USER_ID);

		self::assertSame(['2', '1'], array_column($posts, 'guid'));
		self::assertSame('Reply', $posts[0]['body']);
	}

	public function testPhotoInPostOfOtherAuthorIsIgnored(): void
	{
		$this->addMedia(3, $this->photoUrl(0));

		self::assertSame([], $this->mediaUsage->selectPostsByPhoto(self::RESOURCE_ID, self::USER_ID));
	}

	public function testPhotoIsNotListedForOtherUser(): void
	{
		$this->addMedia(1, $this->photoUrl(0));

		self::assertSame([], $this->mediaUsage->selectPostsByPhoto(self::RESOURCE_ID, self::OTHER_UID));
	}

	public function testDeletedPostIsIgnored(): void
	{
		$this->addMedia(1, $this->photoUrl(0));
		DI::dba()->update('post-user', ['deleted' => true], ['uri-id' => 1, 'uid' => self::USER_ID]);

		self::assertSame([], $this->mediaUsage->selectPostsByPhoto(self::RESOURCE_ID, self::USER_ID));
	}

	public function testResultIsLimited(): void
	{
		$this->addMedia(1, $this->photoUrl(0));
		$this->addMedia(2, $this->photoUrl(0));

		self::assertSame(['2'], array_column($this->mediaUsage->selectPostsByPhoto(self::RESOURCE_ID, self::USER_ID, 1), 'guid'));
	}

	public function testAttachmentIsFoundById(): void
	{
		self::assertTrue(DI::dba()->insert('attach', ['id' => self::ATTACH_ID, 'uid' => self::USER_ID, 'hash' => 'hash', 'filename' => 'report.pdf', 'data' => '']));
		$this->addMedia(2, 'https://friendica.local/attach/' . self::ATTACH_ID, null, self::ATTACH_ID);

		self::assertSame(['2'], array_column($this->mediaUsage->selectPostsByAttachment(self::ATTACH_ID, self::USER_ID), 'guid'));
		self::assertSame([], $this->mediaUsage->selectPostsByAttachment(self::ATTACH_ID + 1, self::USER_ID));
	}
}
