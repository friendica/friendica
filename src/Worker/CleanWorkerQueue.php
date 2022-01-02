<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Delete all done workerqueue entries
 */
class CleanWorkerQueue
{
	public static function execute()
	{
		DBA::delete('workerqueue', ["`done` AND `executed` < ?", DateTimeFormat::utc('now - 1 hour')]);

		// Optimizing this table only last seconds
		if (DI::config()->get('system', 'optimize_tables')) {
			// We are acquiring the two locks from the worker to avoid locking problems
			if (DI::lock()->acquire(Worker::LOCK_PROCESS, 10)) {
				if (DI::lock()->acquire(Worker::LOCK_WORKER, 10)) {
					DBA::e("OPTIMIZE TABLE `workerqueue`");
					DBA::e("OPTIMIZE TABLE `process`");			
					DI::lock()->release(Worker::LOCK_WORKER);
				}
				DI::lock()->release(Worker::LOCK_PROCESS);
			}
		}
	}
}
