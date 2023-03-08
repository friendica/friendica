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

namespace Friendica\Protocol\ActivityPub;

use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * This class handles the fetching of posts
 */
class Fetch
{
	public static function add(string $url): int
	{
		DBA::insert('fetch-entry', ['url' => $url, 'created' => DateTimeFormat::utcNow()], Database::INSERT_IGNORE);

		$fetch = DBA::selectFirst('fetch-entry', ['id'], ['url' => $url]);
		Logger::debug('Added fetch entry', ['url' => $url, 'fetch' => $fetch]);
		return $fetch['id'] ?? 0;
	}

	/**
	 * Set the worker id for the queue entry
	 *
	 * @param array $activity
	 * @param int   $wid
	 * @return void
	 */
	public static function setWorkerId(string $url, int $wid)
	{
		if (empty($url) || empty($wid)) {
			return;
		}

		DBA::update('fetch-entry', ['wid' => $wid], ['url' => $url]);
		Logger::debug('Worker id set', ['url' => $url, 'wid' => $wid]);
	}

	/**
	 * Check if there is an assigned worker task
	 *
	 * @param array $activity
	 * @return bool
	 */
	public static function hasWorker(string $url): bool
	{
		$fetch = DBA::selectFirst('fetch-entry', ['id', 'wid'], ['url' => $url]);
		if (empty($fetch['id'])) {
			Logger::debug('No entry found for url', ['url' => $url]);
			return false;
		}

		// We don't have a workerqueue id yet. So most likely is isn't assigned yet.
		// To avoid the ramping up of another fetch request we simply claim that there is a waiting worker.
		if (!empty($fetch['id']) && empty($fetch['wid'])) {
			Logger::debug('Entry without worker found for url', ['url' => $url]);
			return true;
		}

		return DBA::exists('workerqueue', ['id' => $fetch['wid'], 'done' => false]);
	}
}
