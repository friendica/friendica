<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Exception;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;

class ThreadUser
{
	/**
	 * Insert a new URI user entry
	 *
	 * @return bool   success
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, int $uid, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-thread-user', $data);

		// Additionally assign the key fields
		$fields['uri-id'] = $uri_id;
		$fields['uid']    = $uid;

		return DBA::insert('post-thread-user', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Update a URI user entry
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

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-thread-user', $data);

		// Remove the key fields
		unset($fields['uri-id']);
		unset($fields['uid']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-thread-user', $fields, ['uri-id' => $uri_id, 'uid' => $uid], $insert_if_missing ? true : []);
	}

	/**
	 * Delete a row from the post-thread-user table
	 *
	 * @param array        $conditions Field condition(s)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete(array $conditions)
	{
		return DBA::delete('post-thread-user', $conditions);
	}

	/**
	 * @param int $uri_id
	 * @param int $uid
	 * @return bool
	 * @throws Exception
	 */
	public static function getIgnored(int $uri_id, int $uid)
	{
		$threaduser = DBA::selectFirst('post-thread-user', ['ignored'], ['uri-id' => $uri_id, 'uid' => $uid]);
		if (empty($threaduser)) {
			return false;
		}
		return (bool)$threaduser['ignored'];
	}

	/**
	 * @param int $uri_id
	 * @param int $uid
	 * @param int $ignored
	 * @return void
	 * @throws Exception
	 */
	public static function setIgnored(int $uri_id, int $uid, int $ignored)
	{
		DBA::update('post-thread-user', ['ignored' => $ignored], ['uri-id' => $uri_id, 'uid' => $uid], true);
	}
}
