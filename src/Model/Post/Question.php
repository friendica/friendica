<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Database\DBA;
use Friendica\DI;

class Question
{
	/**
	 * Update a post question entry
	 *
	 * @param integer $uri_id
	 * @param array   $data
	 * @param bool    $insert_if_missing
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, array $data = [], bool $insert_if_missing = true)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-question', $data);

		// Remove the key fields
		unset($fields['uri-id']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-question', $fields, ['uri-id' => $uri_id], $insert_if_missing ? true : []);
	}

	/**
	 * @param integer $id     Question ID
	 * @param array   $fields Array of selected fields, empty for all
	 * @return array|boolean  Question record if it exists, false otherwise
	 */
	public static function getById($id, $fields = [])
	{
		return DBA::selectFirst('post-question', $fields, ['id' => $id]);
	}
}
