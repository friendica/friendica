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

namespace Friendica\Protocol\ActivityPub;

use Friendica\Model\Post;

/**
 * This class prevents maximum function nesting errors by flattening recursive calls to Processor::fetchMissingActivity
 */
class FetchQueue
{
	/** @var FetchQueueItem[] */
	protected $queue = [];

	public function push(FetchQueueItem $item)
	{
		array_push($this->queue, $item);
	}

	/**
	 * Processes missing activities one by one. It is possible that a processing call will add additional missing
	 * activities, they will be processed in subsequent iterations of the loop.
	 *
	 * Since this process is self-contained, it isn't suitable to retrieve the URI of a single activity.
	 *
	 * The simplest way to get the URI of the first activity and ensures all the parents are fetched is this way:
	 *
	 * $fetchQueue = new ActivityPub\FetchQueue();
	 * $fetchedUri = ActivityPub\Processor::fetchMissingActivity($fetchQueue, $activityUri);
	 * $fetchQueue->process();
	 */
	public function process()
	{
		$failedIds = [];

		while (count($this->queue)) {
			$fetchQueueItem = array_pop($this->queue);

			if (!call_user_func_array([Processor::class, 'fetchMissingActivity'], array_merge([$this], $fetchQueueItem->toParameters()))) {
				$failedIds[] = $fetchQueueItem->getUrl();
			}
		}

		// Removing orphans if fetch failed
		Post::delete(['thr-parent' => $failedIds]);
	}
}
