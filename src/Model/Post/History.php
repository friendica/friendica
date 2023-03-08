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

use Friendica\Core\Logger;
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
			Logger::warning('Post not found', ['uri-id' => $uri_id]);
			return;
		}

		if ($item['edited'] <= $post['edited']) {
			Logger::info('New edit date is not newer than the old one', ['uri-id' => $uri_id, 'old' => $post['edited'], 'new' => $item['edited']]);
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
			Logger::info('Added history', ['uri-id' => $uri_id, 'edited' => $post['edited']]);
		} else {
			Logger::info('No content fields had been changed', ['uri-id' => $uri_id, 'edited' => $post['edited']]);
		}
	}
}
