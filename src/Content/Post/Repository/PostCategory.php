<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Post\Repository;

use Friendica\Database\Database;
use Friendica\Model\Post\Category;
use Friendica\Model\Tag;

/**
 * Repository for the folders (the "file" categories) of the posts of a user
 */
class PostCategory
{
	public function __construct(
		private readonly Database $database,
	) {}

	/**
	 * Returns the tag id of a folder name without creating the tag
	 *
	 * Tag names are compared case insensitive, so "Bar" and "bar" result in the same id.
	 *
	 * @param string $name Folder name
	 * @return int|null Tag id or null when there is no such tag
	 */
	public function findFolderId(string $name): ?int
	{
		$tag = $this->database->selectFirst('tag', ['id'], ['name' => substr($name, 0, 96), 'url' => '']);

		return $tag ? (int) $tag['id'] : null;
	}

	/**
	 * Renames a whole folder, moving every post filed under the old name to the new name
	 *
	 * Posts that are already filed under the new name keep that entry, the old entry is removed.
	 *
	 * @param int    $uid
	 * @param string $oldName
	 * @param string $newName
	 * @return bool success
	 * @throws \Exception
	 */
	public function renameFolder(int $uid, string $oldName, string $newName): bool
	{
		$oldTagId = $this->findFolderId($oldName);
		if (empty($oldTagId)) {
			return false;
		}

		$newTagId = Tag::getID($newName);
		if (empty($newTagId) || $newTagId === $oldTagId) {
			return false;
		}

		$this->database->transaction();

		$condition = ['uid' => $uid, 'type' => Category::FILE, 'tid' => $oldTagId];
		if (
			!$this->database->update('post-category', ['tid' => $newTagId], $condition, [], ['ignore' => true])
			|| !$this->database->delete('post-category', $condition)
		) {
			$this->database->rollback();
			return false;
		}

		return $this->database->commit();
	}

	/**
	 * Deletes a whole folder, removing the folder tag from every post filed under it
	 *
	 * @param int    $uid
	 * @param string $name
	 * @return bool success
	 * @throws \Exception
	 */
	public function deleteFolder(int $uid, string $name): bool
	{
		$tagId = $this->findFolderId($name);
		if (empty($tagId)) {
			return false;
		}

		return $this->database->delete('post-category', ['uid' => $uid, 'type' => Category::FILE, 'tid' => $tagId]);
	}
}
