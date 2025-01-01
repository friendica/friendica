<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\Activity;

class User
{
	/**
	 * Insert a new post user entry
	 *
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
		$fields['uid']    = $uid;

		// Public posts and activities (like, dislike, ...) are always seen
		if ($uid == 0 || (($data['gravity'] == Item::GRAVITY_ACTIVITY) && ($data['verb'] != Activity::ANNOUNCE))) {
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
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete(array $conditions)
	{
		return DBA::delete('post-user', $conditions);
	}
}
