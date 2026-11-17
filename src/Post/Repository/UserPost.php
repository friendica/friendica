<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Post\Repository;

use Friendica\Database\Database;
use Generator;

/**
 * The copies of a post that users hold in `post-user` and `post-thread-user`.
 */
final readonly class UserPost
{
	public function __construct(private Database $db) {}

	/**
	 * Reads the matching posts row by row, so a large photo album is never loaded at once.
	 *
	 * @param array $condition condition on `post-user-view`
	 *
	 * @return Generator<array{id: int, uid: int, uri-id: int}>
	 */
	public function selectOwners(array $condition): Generator
	{
		$posts = $this->db->select('post-user-view', ['id', 'uid', 'uri-id'], $condition);
		try {
			while ($post = $this->db->fetch($posts)) {
				yield ['id' => (int) $post['id'], 'uid' => (int) $post['uid'], 'uri-id' => (int) $post['uri-id']];
			}
		} finally {
			$this->db->close($posts);
		}
	}

	/**
	 * Hides a post and its thread from the timeline of a user, also when the user has no copy of it yet.
	 */
	public function hide(int $uriId, int $uid): void
	{
		$this->db->update('post-user', ['hidden' => true], ['uri-id' => $uriId, 'uid' => $uid], true);
		$this->db->update('post-thread-user', ['hidden' => true], ['uri-id' => $uriId, 'uid' => $uid], true);
	}
}
