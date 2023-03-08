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

use \BadMethodCallException;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Post;

class Content
{
	/**
	 * Insert a new post-content entry
	 *
	 * @param integer $uri_id
	 * @param array   $fields
	 * @return bool   success
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-content', $data);

		// Additionally assign the key fields
		$fields['uri-id'] = $uri_id;

		return DBA::insert('post-content', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Update a post content entry
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

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-content', $data);

		// Remove the key fields
		unset($fields['uri-id']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-content', $fields, ['uri-id' => $uri_id], $insert_if_missing ? true : []);
	}

	/**
	 * Delete a row from the post-content table
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
		return DBA::delete('post-content', $conditions, $options);
	}


	/**
	 * Search posts for given content
	 *
	 * @param string $search
	 * @param integer $uid
	 * @param integer $start
	 * @param integer $limit
	 * @param integer $last_uriid
	 * @return array
	 */
	public static function getURIIdListBySearch(string $search, int $uid = 0, int $start = 0, int $limit = 100, int $last_uriid = 0)
	{
		$condition = ["`uri-id` IN (SELECT `uri-id` FROM `post-content` WHERE MATCH (`title`, `content-warning`, `body`) AGAINST (? IN BOOLEAN MODE))
			AND (`uid` = ? OR (`uid` = ? AND NOT `global`)) AND (`network` IN (?, ?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
			str_replace('@', ' ', $search), 0, $uid, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, $uid, 0];

		if (!empty($last_uriid)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $last_uriid]);
		}

		$params = [
			'order' => ['uri-id' => true],
			'limit' => [$start, $limit]
		];

		$tags = Post::select(['uri-id'], $condition, $params);

		$uriids = [];
		while ($tag = DBA::fetch($tags)) {
			$uriids[] = $tag['uri-id'];
		}
		DBA::close($tags);

		return $uriids;
	}

	public static function countBySearch(string $search, int $uid = 0)
	{
		$condition = ["`uri-id` IN (SELECT `uri-id` FROM `post-content` WHERE MATCH (`title`, `content-warning`, `body`) AGAINST (? IN BOOLEAN MODE))
			AND (`uid` = ? OR (`uid` = ? AND NOT `global`)) AND (`network` IN (?, ?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
			str_replace('@', ' ', $search), 0, $uid, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, $uid, 0];
		return Post::count($condition);
	}
}
