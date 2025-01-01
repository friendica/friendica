<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;

/**
 * Removes orphaned data from deleted users
 */
class RemoveUser
{
	/**
	 * Removes user by id
	 *
	 * @param int $uid User id
	 * @return void
	 */
	public static function execute(int $uid)
	{
		// Only delete if the user is archived
		$condition = ['account_removed' => true, 'uid' => $uid];
		if (!DBA::exists('user', $condition)) {
			return;
		}

		// Now we delete all user items
		$condition = ['uid' => $uid, 'deleted' => false];
		do {
			$items = Post::select(['id'], $condition, ['limit' => 100]);
			while ($item = Post::fetch($items)) {
				Item::markForDeletionById($item['id'], Worker::PRIORITY_NEGLIGIBLE);
			}
			DBA::close($items);
		} while (Post::exists($condition));
	}
}
