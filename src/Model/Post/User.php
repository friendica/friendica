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

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use \BadMethodCallException;
use Friendica\Database\Database;
use Friendica\DI;

class User
{
	/**
	 * Insert a new post user entry
	 *
	 * @param integer $uri_id
	 * @param integer $uid
	 * @param array   $fields
	 * @return int    ID of inserted post-user
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, int $uid, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-user', $data);

		// Additionally assign the key fields
		$fields['uri-id'] = $uri_id;
		$fields['uid'] = $uid;

		// Public posts are always seen
		if ($uid == 0) {
			$fields['unseen'] = false;
		}

		// Does the entry already exist?
		if (DBA::exists('post-user', ['uri-id' => $uri_id, 'uid' => $uid])) {
			$postuser = DBA::selectFirst('post-user', [], ['uri-id' => $uri_id, 'uid' => $uid]);

			// We quit here, when there are obvious differences
			foreach (['created', 'owner-id', 'author-id', 'vid', 'network', 'private', 'wall', 'origin'] as $key) {
				if ($fields[$key] != $postuser[$key]) {
					return 0;
				}
			}

			$update = [];
			foreach (['gravity', 'parent-uri-id', 'thr-parent-id'] as $key) {
				if ($fields[$key] != $postuser[$key]) {
					$update[$key] = $fields[$key];
				}
			}

			// When the parents changed, we apply these changes to the existing entry
			if (!empty($update)) {
				DBA::update('post-user', $update, ['id' => $postuser['id']]);
				return $postuser['id'];
			} else {
				return 0;
			}
		}

		if (!DBA::insert('post-user', $fields, Database::INSERT_IGNORE)) {
			return 0;
		}

		return DBA::lastInsertId();
	}

	/**
	 * Update a post user entry
	 *
	 * @param integer $uri_id
	 * @param integer $uid
	 * @param array   $data
	 * @param bool    $insert_if_missing
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, int $uid, array $data = [], bool $insert_if_missing = false)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-user', $data);

		// Remove the key fields
		unset($fields['uri-id']);
		unset($fields['uid']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-user', $fields, ['uri-id' => $uri_id, 'uid' => $uid], $insert_if_missing ? true : []);
	}

	/**
	 * Delete a row from the post-user table
	 *
	 * @param array        $conditions Field condition(s)
	 * @param array        $options
	 *                           - cascade: If true we delete records in other tables that depend on the one we're deleting through
	 *                           relations (default: true)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete(array $conditions, array $options = [])
	{
		return DBA::delete('post-user', $conditions, $options);
	}
}
