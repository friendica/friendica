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

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

class Activity
{
	/**
	 * Insert a new post-activity entry
	 *
	 * @param integer $uri_id
	 * @param array   $fields
	 *
	 * @return bool   success
	 */
	public static function insert(int $uri_id, string $source): bool
	{
		// Additionally assign the key fields
		$fields = [
			'uri-id'   => $uri_id,
			'activity' => $source,
			'received' => DateTimeFormat::utcNow()
		];

		return DBA::insert('post-activity', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Retrieves activity of the given uri-id
	 *
	 * @param int   $uriId
	 *
	 * @return array
	 */
	public static function getByURIId(int $uriId): array
	{
		$activity = DBA::selectFirst('post-activity', [], ['uri-id' => $uriId]);
		return json_decode($activity['activity'] ?? '', true) ?? [];
	}

	/**
	 * Checks if the given uridid has a stored activity
	 *
	 * @param integer $uriId
	 *
	 * @return boolean
	 */
	public static function exists(int $uriId): bool
	{
		return DBA::exists('post-activity', ['uri-id' => $uriId]);
	}
}
