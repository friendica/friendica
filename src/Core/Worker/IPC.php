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

namespace Friendica\Core\Worker;

use Friendica\Database\DBA;

/**
 * Contains the class for the inter process communication
 */
class IPC
{
	/**
	 * Set the flag if some job is waiting
	 *
	 * @param boolean $jobs Is there a waiting job?
	 * @param int $key Key number
	 * @throws \Exception
	 */
	public static function SetJobState(bool $jobs, int $key = 0)
	{
		$stamp = (float)microtime(true);
		DBA::replace('worker-ipc', ['jobs' => $jobs, 'key' => $key]);
	}

	/**
	 * Delete a key entry
	 *
	 * @param int $key Key number
	 * @throws \Exception
	 */
	public static function DeleteJobState(int $key)
	{
		$stamp = (float)microtime(true);
		DBA::delete('worker-ipc', ['key' => $key]);
	}

	/**
	 * Checks if some worker job waits to be executed
	 *
	 * @param int $key Key number
	 * @return bool
	 * @throws \Exception
	 */
	public static function JobsExists(int $key = 0)
	{
		$row = DBA::selectFirst('worker-ipc', ['jobs'], ['key' => $key]);

		// When we don't have a row, no job is running
		if (!DBA::isResult($row)) {
			return false;
		}

		return (bool)$row['jobs'];
	}
}
