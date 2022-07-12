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
	/** @var \Psr\Log\LoggerInterface */
	private $logger;

	public function __construct(\Psr\Log\LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

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
		$lastId = null;
		$failedIds = [];

		// First pass, we fetch the ancestors
		for ($i = 0; $i < count($this->queue); $i++) {
			$fetchQueueItem = $this->queue[$i];
			if (!call_user_func_array([Processor::class, 'fetchMissingActivity'], array_merge([$this], $fetchQueueItem->toParameters()))) {
				$failedIds[] = $fetchQueueItem->getUrl();
			}
		}

		// Second pass for the conversation in reverse order to ensure we have parents starting with the top-level
		// We use a foreach so that if an activity was missing and the item wasn't created, we don't keep adding
		// the missing parent to the queue
		foreach(array_reverse($this->queue) as $fetchQueueItem) {
			if (!$lastId = call_user_func_array([Processor::class, 'fetchMissingActivity'], array_merge([$this], $fetchQueueItem->toParameters()))) {
				$failedIds[] = $fetchQueueItem->getUrl();
			}
		}

		$this->logger->notice('Deleting orphan items (dry run)', ['thr-parent' => $failedIds]);
		// Removing orphans if fetch failed
		//Post::delete(['thr-parent' => $failedIds]);

		// Returns the object id of the first item that was meant to be fetched in the first place
		return $lastId;
	}
}
