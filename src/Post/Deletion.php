<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Post;

use Friendica\Core\Worker;
use Friendica\Model\Item;
use Friendica\Post\Repository\UserPost;
use Psr\Log\LoggerInterface;

/**
 * Deletes posts on behalf of a user
 */
final readonly class Deletion
{
	public function __construct(
		private UserPost $userPosts,
		private LoggerInterface $logger,
	) {}

	/**
	 * Deletes the user's own matching posts and hides public posts from their timeline.
	 * Posts of other users are left alone.
	 *
	 * @param array $condition condition on `post-user-view`
	 */
	public function deleteForUser(array $condition, int $uid): void
	{
		if ($uid === 0) {
			return;
		}

		foreach ($this->userPosts->selectOwners($condition) as $post) {
			if (in_array($post['uid'], [$uid, 0], true)) {
				$this->userPosts->hide($post['uri-id'], $uid);
			}

			if ($post['uid'] === $uid) {
				Item::markForDeletionById($post['id'], Worker::PRIORITY_HIGH);
			} elseif ($post['uid'] !== 0) {
				$this->logger->warning('Wrong ownership. Not deleting item', ['id' => $post['id']]);
			}
		}
	}
}
