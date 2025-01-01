<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\DI;

class Origin
{
	/**
	 * Insert a new post origin entry
	 *
	 * @return boolean was the insert successful?
	 * @throws \Exception
	 */
	public static function insert(array $data = []): bool
	{
		if (!$data['origin'] || ($data['uid'] == 0)) {
			return false;
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-origin', $data);

		return DBA::insert('post-origin', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Update a post origin entry
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

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-origin', $data);

		// Remove the key fields
		unset($fields['uri-id']);
		unset($fields['uid']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-origin', $fields, ['uri-id' => $uri_id, 'uid' => $uid], $insert_if_missing ? true : []);
	}

	/**
	 * Delete a row from the post-origin table
	 *
	 * @param array        $conditions Field condition(s)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete(array $conditions)
	{
		return DBA::delete('post-origin', $conditions);
	}
}
