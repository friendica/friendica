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
use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\ActivityPub;

class Collection
{
	const FEATURED = 0;

	/**
	 * Add a post to a collection
	 *
	 * @param integer $uri_id
	 * @param integer $type
	 * @param integer $author_id
	 * @param integer $cache_uid If set to a non zero value, the featured cache is cleared
	 */
	public static function add(int $uri_id, int $type, int $author_id, int $cache_uid = 0)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		DBA::insert('post-collection', ['uri-id' => $uri_id, 'type' => $type, 'author-id' => $author_id], Database::INSERT_IGNORE);

		if (!empty($cache_uid) && ($type == self::FEATURED)) {
			DI::cache()->delete(ActivityPub\Transmitter::CACHEKEY_FEATURED . $cache_uid);
		}
	}

	/**
	 * Remove a post from a collection
	 *
	 * @param integer $uri_id
	 * @param integer $type
	 * @param integer $cache_uid If set to a non zero value, the featured cache is cleared
	 */
	public static function remove(int $uri_id, int $type, int $cache_uid = 0)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		DBA::delete('post-collection', ['uri-id' => $uri_id, 'type' => $type]);

		if (!empty($cache_uid) && ($type == self::FEATURED)) {
			DI::cache()->delete(ActivityPub\Transmitter::CACHEKEY_FEATURED . $cache_uid);
		}
	}

	/**
	 * Fetch collections for a given contact
	 *
	 * @param integer $cid
	 * @param [type] $type
	 * @param array $fields
	 * @return array
	 */
	public static function selectToArrayForContact(int $cid, int $type = self::FEATURED, array $fields = [])
	{
		return DBA::selectToArray('collection-view', $fields, ['cid' => $cid, 'private' => [Item::PUBLIC, Item::UNLISTED], 'deleted' => false, 'type' => $type]);
	}
}
