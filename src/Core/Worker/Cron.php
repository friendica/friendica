<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Worker;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * Contains the class for jobs that are executed in an interval
 */
class Cron
{
	/**
	 * Runs the cron processes
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function run()
	{
		DI::logger()->info('Add cron entries');

		// Check for spooled items
		Worker::add(['priority' => Worker::PRIORITY_HIGH, 'force_priority' => true], 'SpoolPost');

		// Run the cron job that calls all other jobs
		Worker::add(['priority' => Worker::PRIORITY_MEDIUM, 'force_priority' => true], 'Cron');

		// Cleaning dead processes
		self::killStaleWorkers();

		// Remove old entries from the workerqueue
		self::cleanWorkerQueue();

		// Directly deliver or requeue posts to ActivityPub systems
		self::deliverAPPosts();

		// Directly deliver or requeue posts to other systems
		self::deliverPosts();

		// Automatically open/close the registration based on the user count
		User::setRegisterMethodByUserCount();
	}

	/**
	 * fix the queue entry if the worker process died
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function killStaleWorkers()
	{
		$entries = DBA::select(
			'workerqueue',
			['id', 'pid', 'executed', 'priority', 'command', 'parameter'],
			['NOT `done` AND `pid` != ? AND `executed` > ?', 0, DBA::NULL_DATETIME],
			['order' => ['priority', 'retrial', 'created']]
		);

		$max_duration_defaults = DI::config()->get('system', 'worker_max_duration');

		while ($entry = DBA::fetch($entries)) {
			if (!posix_kill($entry["pid"], 0)) {
				DBA::update('workerqueue', ['executed' => DBA::NULL_DATETIME, 'pid' => 0], ['id' => $entry["id"]]);
			} else {
				// Kill long running processes

				// Define the maximum durations
				$max_duration = $max_duration_defaults[$entry['priority']] ?? 0;
				if (empty($max_duration)) {
					continue;
				}

				$argv = json_decode($entry['parameter'], true);
				if (!empty($entry['command'])) {
					$command = $entry['command'];
				} elseif (!empty($argv)) {
					$command = array_shift($argv);
				} else {
					return;
				}

				$command = basename($command);

				// How long is the process already running?
				$duration = (time() - strtotime($entry["executed"])) / 60;
				if ($duration > $max_duration) {
					DI::logger()->warning('Worker process took too much time - killed', ['duration' => number_format($duration, 3), 'max' => $max_duration, 'id' => $entry["id"], 'pid' => $entry["pid"], 'command' => $command]);
					posix_kill($entry["pid"], SIGTERM);

					// We killed the stale process.
					// To avoid a blocking situation we reschedule the process at the beginning of the queue.
					// Additionally we are lowering the priority. (But not PRIORITY_CRITICAL)
					$new_priority = $entry['priority'];
					if ($entry['priority'] == Worker::PRIORITY_HIGH) {
						$new_priority = Worker::PRIORITY_MEDIUM;
					} elseif ($entry['priority'] == Worker::PRIORITY_MEDIUM) {
						$new_priority = Worker::PRIORITY_LOW;
					} elseif ($entry['priority'] != Worker::PRIORITY_CRITICAL) {
						$new_priority = Worker::PRIORITY_NEGLIGIBLE;
					}
					DBA::update(
						'workerqueue',
						['executed' => DBA::NULL_DATETIME, 'created' => DateTimeFormat::utcNow(), 'priority' => $new_priority, 'pid' => 0],
						['id'       => $entry["id"]]
					);
				} else {
					DI::logger()->info('Process runtime is okay', ['duration' => number_format($duration, 3), 'max' => $max_duration, 'id' => $entry["id"], 'pid' => $entry["pid"], 'command' => $command]);
				}
			}
		}
		DBA::close($entries);
	}

	/**
	 * Remove old entries from the workerqueue
	 *
	 * @return void
	 */
	private static function cleanWorkerQueue()
	{
		DBA::delete('workerqueue', ["`done` AND `executed` < ?", DateTimeFormat::utc('now - 1 hour')]);

		// Optimizing this table only last seconds
		if (DI::config()->get('system', 'optimize_tables')) {
			// We are acquiring the two locks from the worker to avoid locking problems
			if (DI::lock()->acquire(Worker::LOCK_PROCESS, 10)) {
				if (DI::lock()->acquire(Worker::LOCK_WORKER, 10)) {
					DBA::optimizeTable('workerqueue');
					DBA::optimizeTable('process');
					DI::lock()->release(Worker::LOCK_WORKER);
				}
				DI::lock()->release(Worker::LOCK_PROCESS);
			}
		}
	}

	/**
	 * Directly deliver AP messages or requeue them.
	 *
	 * This function is placed here as a safeguard. Even when the worker queue is completely blocked, messages will be delivered.
	 */
	private static function deliverAPPosts()
	{
		$deliveries = DBA::p("SELECT `item-uri`.`uri` AS `inbox`, MAX(`gsid`) AS `gsid`, MAX(`shared`) AS `shared`, MAX(`failed`) AS `failed` FROM `post-delivery` INNER JOIN `item-uri` ON `item-uri`.`id` = `post-delivery`.`inbox-id` LEFT JOIN `inbox-status` ON `inbox-status`.`url` = `item-uri`.`uri` GROUP BY `inbox` ORDER BY RAND()");
		while ($delivery = DBA::fetch($deliveries)) {
			if ($delivery['failed'] > 0) {
				DI::logger()->info('Removing failed deliveries', ['inbox' => $delivery['inbox'], 'failed' => $delivery['failed']]);
				Post\Delivery::removeFailed($delivery['inbox']);
			}
			if (($delivery['failed'] == 0) && $delivery['shared'] && !empty($delivery['gsid']) && GServer::isReachableById($delivery['gsid'])) {
				$result = ActivityPub\Delivery::deliver($delivery['inbox']);
				DI::logger()->info('Directly deliver inbox', ['inbox' => $delivery['inbox'], 'result' => $result['success']]);
				continue;
			} elseif ($delivery['failed'] < 3) {
				$priority = Worker::PRIORITY_HIGH;
			} elseif ($delivery['failed'] < 6) {
				$priority = Worker::PRIORITY_MEDIUM;
			} elseif ($delivery['failed'] < 8) {
				$priority = Worker::PRIORITY_LOW;
			} else {
				$priority = Worker::PRIORITY_NEGLIGIBLE;
			}

			if (Worker::add(['priority' => $priority, 'force_priority' => true], 'APDelivery', '', 0, $delivery['inbox'], 0)) {
				DI::logger()->info('Priority for APDelivery worker adjusted', ['inbox' => $delivery['inbox'], 'failed' => $delivery['failed'], 'priority' => $priority]);
			}
		}

		DBA::close($deliveries);

		// Optimizing this table only last seconds
		if (DI::config()->get('system', 'optimize_tables')) {
			DI::logger()->info('Optimize start');
			DBA::optimizeTable('post-delivery');
			DI::logger()->info('Optimize end');
		}
	}

	/**
	 * Directly deliver messages or requeue them.
	 */
	private static function deliverPosts()
	{
		foreach(DI::deliveryQueueItemRepo()->selectAggregateByServerId() as $delivery) {
			if ($delivery->failed > 0) {
				DI::logger()->info('Removing failed deliveries', ['gsid' => $delivery->targetServerId, 'failed' => $delivery->failed]);
				DI::deliveryQueueItemRepo()->removeFailedByServerId($delivery->targetServerId, DI::config()->get('system', 'worker_defer_limit'));
			}

			if (($delivery->failed < 3) || GServer::isReachableById($delivery->targetServerId)) {
				$priority = Worker::PRIORITY_HIGH;
			} elseif ($delivery->failed < 6) {
				$priority = Worker::PRIORITY_MEDIUM;
			} elseif ($delivery->failed < 8) {
				$priority = Worker::PRIORITY_LOW;
			} else {
				$priority = Worker::PRIORITY_NEGLIGIBLE;
			}

			if (Worker::add(['priority' => $priority, 'force_priority' => true], 'BulkDelivery', $delivery->targetServerId)) {
				DI::logger()->info('Priority for BulkDelivery worker adjusted', ['gsid' => $delivery->targetServerId, 'failed' => $delivery->failed, 'priority' => $priority]);
			}
		}

		// Optimizing this table only last seconds
		if (DI::config()->get('system', 'optimize_tables')) {
			DI::logger()->info('Optimize start');
			DI::deliveryQueueItemRepo()->optimizeStorage();
			DI::logger()->info('Optimize end');
		}
	}

	/**
	 * Add missing "intro" records.
	 *
	 * @return void
	 */
	private static function addIntros()
	{
		$contacts = DBA::p("SELECT `uid`, `id`, `created` FROM `contact` WHERE `rel` = ? AND `pending` AND NOT `id` IN (SELECT `contact-id` FROM `intro`)", Contact::FOLLOWER);
		while ($contact = DBA::fetch($contacts)) {
			$fields = [
				'uid'        => $contact['uid'],
				'contact-id' => $contact['id'],
				'datetime'   => $contact['created'],
				'hash'       => Strings::getRandomHex()
			];
			DI::logger()->notice('Adding missing intro', ['fields' => $fields]);
			DBA::insert('intro', $fields);
		}
	}
}
