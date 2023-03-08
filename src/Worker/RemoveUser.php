<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;

/**
 * Removes orphaned data from deleted users
 */
class RemoveUser {
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
