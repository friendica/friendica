<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Database\DBA;
use Friendica\DI;

class QuestionOption
{
	/**
	 * Update a post question-option entry
	 *
	 * @param integer $uri_id
	 * @param integer $id
	 * @param array   $data
	 * @param bool    $insert_if_missing
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, int $id, array $data = [], bool $insert_if_missing = true)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-question-option', $data);

		// Remove the key fields
		unset($fields['uri-id']);
		unset($fields['id']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-question-option', $fields, ['uri-id' => $uri_id, 'id' => $id], $insert_if_missing ? true : []);
	}

	/**
	 * Retrieves the question options associated with the provided item ID.
	 *
	 * @param int $uri_id
	 * @return array
	 * @throws \Exception
	 */
	public static function getByURIId(int $uri_id)
	{
		$condition = ['uri-id' => $uri_id];

		return DBA::selectToArray('post-question-option', [], $condition, ['order' => ['id']]);
	}
}
