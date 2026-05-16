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

class Quote
{
	/**
	 * Insert a new post-quote entry
	 *
	 * @return bool   success
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-quote', $data);

		// Additionally assign the key fields
		$fields['uri-id'] = $uri_id;

		return DBA::insert('post-quote', $fields, Database::INSERT_UPDATE);
	}

	/**
	 * Update a post quote entry
	 *
	 * @param integer $uri_id
	 * @param array   $data
	 * @param bool    $insert_if_missing
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, array $data = [], bool $insert_if_missing = false)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-quote', $data);

		// Remove the key fields
		unset($fields['uri-id']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-quote', $fields, ['uri-id' => $uri_id], $insert_if_missing ? true : []);
	}

	/**
	 * Delete a row from the post-quote table
	 *
	 * @param array        $conditions Field condition(s)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete(array $conditions)
	{
		return DBA::delete('post-quote', $conditions);
	}

	/**
	 * Check if a post-quote entry exists for the given URI ID
	 *
	 * @param integer $uri_id
	 * @return boolean exists?
	 */
	public static function exists(int $uri_id): bool
	{
		return DBA::exists('post-quote', ['uri-id' => $uri_id]);
	}

	/**
	 * Check if a post-quote entry exists that quotes the given URI ID
	 *
	 * @param integer $uri_id
	 * @return boolean exists?
	 */
	public static function existsQuote(int $uri_id): bool
	{
		return DBA::exists('post-quote', ['quote-uri-id' => $uri_id]);
	}
}
