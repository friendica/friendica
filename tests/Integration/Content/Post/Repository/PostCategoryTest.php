<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Content\Post\Repository;

use Friendica\Content\Post\Repository\PostCategory;
use Friendica\DI;
use Friendica\Model\Post\Category;
use Friendica\Model\Tag;
use Friendica\Test\FixtureTestCase;

/** Renames and deletes folders of a user, based on api.fixture.php. */
class PostCategoryTest extends FixtureTestCase
{
	private const USER_ID   = 42;
	private const OTHER_UID = 4242;

	private PostCategory $postCategory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->postCategory = DI::getDice()->create(PostCategory::class);

		self::assertTrue(DI::dba()->insert('user', ['uid' => self::OTHER_UID, 'nickname' => 'otheruser', 'email' => 'other@example.com']));
	}

	private function file(int $uid, int $uriId, string $folder): void
	{
		self::assertTrue(DI::dba()->insert('post-category', ['uri-id' => $uriId, 'uid' => $uid, 'type' => Category::FILE, 'tid' => Tag::getID($folder)]));
	}

	/** @return int[] */
	private function postsInFolder(int $uid, string $folder): array
	{
		$tagId = $this->postCategory->findFolderId($folder);
		if ($tagId === null) {
			return [];
		}

		$rows = DI::dba()->selectToArray('post-category', ['uri-id'], ['uid' => $uid, 'type' => Category::FILE, 'tid' => $tagId], ['order' => ['uri-id']]);

		return array_map('intval', array_column($rows, 'uri-id'));
	}

	public function testFindFolderIdDoesNotCreateTag(): void
	{
		self::assertNull($this->postCategory->findFolderId('Missing folder'));
		self::assertFalse(DI::dba()->exists('tag', ['name' => 'Missing folder']));
	}

	public function testFindFolderIdIsCaseInsensitive(): void
	{
		$tagId = Tag::getID('Bar');

		self::assertSame($tagId, $this->postCategory->findFolderId('bar'));
		self::assertSame($tagId, $this->postCategory->findFolderId('BAR'));
	}

	public function testRenameMovesAllPostsOfTheUser(): void
	{
		$this->file(self::USER_ID, 1, 'Foo');
		$this->file(self::USER_ID, 2, 'Foo');

		self::assertTrue($this->postCategory->renameFolder(self::USER_ID, 'Foo', 'Bar'));

		self::assertSame([], $this->postsInFolder(self::USER_ID, 'Foo'));
		self::assertSame([1, 2], $this->postsInFolder(self::USER_ID, 'Bar'));
	}

	public function testRenameIntoExistingFolderMerges(): void
	{
		$this->file(self::USER_ID, 1, 'Foo');
		$this->file(self::USER_ID, 2, 'Foo');
		$this->file(self::USER_ID, 2, 'Bar');
		$this->file(self::USER_ID, 3, 'Bar');

		self::assertTrue($this->postCategory->renameFolder(self::USER_ID, 'Foo', 'Bar'));

		self::assertSame([], $this->postsInFolder(self::USER_ID, 'Foo'));
		self::assertSame([1, 2, 3], $this->postsInFolder(self::USER_ID, 'Bar'));
	}

	public function testRenameKeepsFoldersOfOtherUsers(): void
	{
		$this->file(self::USER_ID, 1, 'Foo');
		$this->file(self::OTHER_UID, 1, 'Foo');

		self::assertTrue($this->postCategory->renameFolder(self::USER_ID, 'Foo', 'Bar'));

		self::assertSame([1], $this->postsInFolder(self::OTHER_UID, 'Foo'));
		self::assertSame([], $this->postsInFolder(self::OTHER_UID, 'Bar'));
	}

	public function testRenameOnlyChangingCaseFails(): void
	{
		$this->file(self::USER_ID, 1, 'Bar');

		self::assertFalse($this->postCategory->renameFolder(self::USER_ID, 'Bar', 'bar'));
		self::assertSame([1], $this->postsInFolder(self::USER_ID, 'Bar'));
	}

	public function testRenameOfMissingFolderFailsWithoutCreatingTags(): void
	{
		self::assertFalse($this->postCategory->renameFolder(self::USER_ID, 'Missing folder', 'New folder'));

		self::assertFalse(DI::dba()->exists('tag', ['name' => 'Missing folder']));
		self::assertFalse(DI::dba()->exists('tag', ['name' => 'New folder']));
	}

	public function testDeleteRemovesOnlyTheFolderOfTheUser(): void
	{
		$this->file(self::USER_ID, 1, 'Foo');
		$this->file(self::USER_ID, 2, 'Bar');
		$this->file(self::OTHER_UID, 1, 'Foo');

		self::assertTrue($this->postCategory->deleteFolder(self::USER_ID, 'foo'));

		self::assertSame([], $this->postsInFolder(self::USER_ID, 'Foo'));
		self::assertSame([2], $this->postsInFolder(self::USER_ID, 'Bar'));
		self::assertSame([1], $this->postsInFolder(self::OTHER_UID, 'Foo'));
	}

	public function testDeleteOfMissingFolderFailsWithoutCreatingTag(): void
	{
		self::assertFalse($this->postCategory->deleteFolder(self::USER_ID, 'Missing folder'));
		self::assertFalse(DI::dba()->exists('tag', ['name' => 'Missing folder']));
	}
}
