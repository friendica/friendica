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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ActivityPub\Queue;
use Friendica\Protocol\ActivityPub\Receiver;

class FetchMissingActivity
{
	/**
	 * Fetch missing activities
	 * @param string $url Contact URL
	 *
	 * @return void
	 */
	public static function execute(string $url, array $child = [], string $relay_actor = '', int $completion = Receiver::COMPLETION_MANUAL)
	{
		Logger::info('Start fetching missing activity', ['url' => $url]);
		$result = ActivityPub\Processor::fetchMissingActivity($url, $child, $relay_actor, $completion);
		if ($result) {
			Logger::info('Successfully fetched missing activity', ['url' => $url]);
		} elseif (!Worker::defer()) {
			Logger::info('Activity could not be fetched', ['url' => $url]);

			// recursively delete all entries that belong to this worker task
			$queue = DI::app()->getQueue();
			if (!empty($queue['id'])) {
				Queue::deleteByWorkerId($queue['id']);
			}
		} else {
			Logger::info('Fetching deferred', ['url' => $url]);
		}
	}
}
