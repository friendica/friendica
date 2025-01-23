<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Post;

class History
{
	/**
	 * Add a post to the history before it is changed
	 *
	 * @param integer $uri_id
	 * @param array   $item
	 */
	public static function add(int $uri_id, array $item)
	{
		$allfields = DI::dbaDefinition()->getAll();
		$fields    = array_keys($allfields['post-history']['fields']);

		$post = Post::selectFirstPost($fields, ['uri-id' => $uri_id]);
		if (empty($post)) {
			DI::logger()->warning('Post not found', ['uri-id' => $uri_id]);
			return;
		}

		if ($item['edited'] <= $post['edited']) {
			DI::logger()->info('New edit date is not newer than the old one', ['uri-id' => $uri_id, 'old' => $post['edited'], 'new' => $item['edited']]);
			return;
		}

		$update  = false;
		$changed = DI::dbaDefinition()->truncateFieldsForTable('post-history', $item);
		unset($changed['uri-id']);
		unset($changed['edited']);
		foreach ($changed as $field => $content) {
			if ($content != $post[$field]) {
				$update = true;
			}
		}

		if ($update) {
			DBA::insert('post-history', $post, Database::INSERT_IGNORE);
			DI::logger()->info('Added history', ['uri-id' => $uri_id, 'edited' => $post['edited']]);
		} else {
			DI::logger()->info('No content fields had been changed', ['uri-id' => $uri_id, 'edited' => $post['edited']]);
		}
	}
}
