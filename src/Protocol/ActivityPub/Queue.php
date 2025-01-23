<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\ActivityPub;

use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\ItemURI;
use Friendica\Model\Post;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\JsonLD;

/**
 * This class handles the processing of incoming posts
 */
class Queue
{
	/**
	 * Add activity to the queue
	 *
	 * @param array $activity
	 * @param string $type
	 * @param integer $uid
	 * @param string $http_signer
	 * @param boolean $push
	 * @return array
	 */
	public static function add(array $activity, string $type, int $uid, string $http_signer, bool $push, bool $trust_source): array
	{
		$fields = [
			'activity-id' => $activity['id'],
			'object-id'   => $activity['object_id'],
			'type'        => $type,
			'object-type' => $activity['object_type'],
			'activity'    => json_encode($activity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
			'received'    => DateTimeFormat::utcNow(),
			'push'        => $push,
			'trust'       => $trust_source,
		];

		if (!empty($activity['reply-to-id'])) {
			$fields['in-reply-to-id'] = $activity['reply-to-id'];
		}

		if (!empty($activity['context'])) {
			$fields['context'] = $activity['context'];
		}

		if (!empty($activity['conversation'])) {
			$fields['conversation'] = $activity['conversation'];
		}

		if (!empty($activity['object_object_type'])) {
			$fields['object-object-type'] = $activity['object_object_type'];
		}

		if (!empty($http_signer)) {
			$fields['signer'] = $http_signer;
		}

		DBA::insert('inbox-entry', $fields, Database::INSERT_IGNORE);

		$queue = DBA::selectFirst('inbox-entry', ['id'], ['activity-id' => $activity['id']]);
		if (!empty($queue['id'])) {
			$activity['entry-id'] = $queue['id'];
			DBA::insert('inbox-entry-receiver', ['queue-id' => $queue['id'], 'uid' => $uid], Database::INSERT_IGNORE);
		}
		return $activity;
	}

	/**
	 * Checks if an entry for a given url and type already exists
	 *
	 * @param string $url
	 * @param string $type
	 * @return boolean
	 */
	public static function exists(string $url, string $type): bool
	{
		return DBA::exists('inbox-entry', ['type' => $type, 'object-id' => $url]);
	}

	/**
	 * Remove activity from the queue
	 *
	 * @param array $activity
	 * @return void
	 */
	public static function remove(array $activity = [])
	{
		if (empty($activity['entry-id'])) {
			return;
		}
		DBA::delete('inbox-entry', ['id' => $activity['entry-id']]);
	}

	/**
	 * Delete all entries that depend on the given worker id
	 *
	 * @param integer $wid
	 * @return void
	 */
	public static function deleteByWorkerId(int $wid)
	{
		$entries = DBA::select('inbox-entry', ['id'], ['wid' => $wid]);
		while ($entry = DBA::fetch($entries)) {
			self::deleteById($entry['id']);
		}
		DBA::close($entries);
	}

	/**
	 * Delete recursively an entry and all their children
	 *
	 * @param integer $id
	 * @return void
	 */
	public static function deleteById(int $id)
	{
		$entry = DBA::selectFirst('inbox-entry', ['id', 'object-id'], ['id' => $id]);
		if (empty($entry)) {
			return;
		}

		DI::logger()->debug('Delete inbox-entry', ['id' => $entry['id']]);

		DBA::delete('inbox-entry', ['id' => $entry['id']]);

		$children = DBA::select('inbox-entry', ['id'], ['in-reply-to-id' => $entry['object-id']]);
		while ($child = DBA::fetch($children)) {
			self::deleteById($child['id']);
		}
		DBA::close($children);
	}

	/**
	 * Set the worker id for the queue entry
	 *
	 * @param int $entry_id
	 * @param int $wid
	 * @return void
	 */
	public static function setWorkerId(int $entry_id, int $wid)
	{
		if (empty($entry_id) || empty($wid)) {
			return;
		}
		DBA::update('inbox-entry', ['wid' => $wid], ['id' => $entry_id]);
	}

	/**
	 * Check if there is an assigned worker task
	 *
	 * @param int $wid
	 *
	 * @return bool
	 */
	public static function hasWorker(int $wid): bool
	{
		if (empty($wid)) {
			return false;
		}
		return DBA::exists('workerqueue', ['id' => $wid, 'done' => false]);
	}

	/**
	 * Process the activity with the given id
	 *
	 * @param integer $id
	 * @param bool    $fetch_parents
	 * @param array   $parent
	 *
	 * @return bool
	 */
	public static function process(int $id, bool $fetch_parents = true, array $parent = []): bool
	{
		$entry = DBA::selectFirst('inbox-entry', [], ['id' => $id]);
		if (empty($entry)) {
			return false;
		}

		if (!self::isProcessable($id)) {
			DI::logger()->debug('Other queue entries need to be processed first.', ['id' => $id]);
			return false;
		}

		if (!empty($entry['wid'])) {
			$worker = DI::appHelper()->getQueue();
			$wid    = $worker['id'] ?? 0;
			if ($entry['wid'] != $wid) {
				$workerqueue = DBA::selectFirst('workerqueue', ['pid'], ['id' => $entry['wid'], 'done' => false]);
				if (!empty($workerqueue['pid']) && posix_kill($workerqueue['pid'], 0)) {
					DI::logger()->notice('Entry is already processed via another process.', ['current' => $wid, 'processor' => $entry['wid']]);
					return false;
				}
			}
		}

		DI::logger()->debug('Processing queue entry', ['id' => $entry['id'], 'type' => $entry['type'], 'object-type' => $entry['object-type'], 'uri' => $entry['object-id'], 'in-reply-to' => $entry['in-reply-to-id']]);

		$activity = json_decode($entry['activity'], true);
		$type     = $entry['type'];
		$push     = $entry['push'];

		$activity['entry-id']        = $entry['id'];
		$activity['worker-id']       = $entry['wid'];
		$activity['recursion-depth'] = 0;

		if (!empty($parent['children'])) {
			$activity['children'] = array_merge($activity['children'] ?? [], $parent['children']);
		}

		if (!empty($parent['callstack'])) {
			$activity['callstack'] = array_merge($activity['callstack'] ?? [], $parent['callstack']);
		}

		if (empty($activity['thread-children-type'])) {
			$activity['thread-children-type'] = $type;
		}

		$receivers = DBA::select('inbox-entry-receiver', ['uid'], ["`queue-id` = ? AND `uid` != ?", $entry['id'], 0]);
		while ($receiver = DBA::fetch($receivers)) {
			if (!in_array($receiver['uid'], $activity['receiver'])) {
				$activity['receiver'][] = $receiver['uid'];
			}
		}
		DBA::close($receivers);

		if (!Receiver::routeActivities($activity, $type, $push, $fetch_parents, $activity['receiver'][0] ?? 0)) {
			self::remove($activity);
		}

		return true;
	}

	/**
	 * Process all activities
	 *
	 * @return void
	 */
	public static function processAll()
	{
		$expired_days = max(1, DI::config()->get('system', 'queue_expired_days'));
		$max_retrial  = max(3, DI::config()->get('system', 'queue_retrial'));

		$entries = DBA::select('inbox-entry', ['id', 'type', 'object-type', 'object-id', 'in-reply-to-id', 'received', 'trust', 'retrial'], ["`wid` IS NULL"], ['order' => ['retrial', 'id' => true]]);
		while ($entry = DBA::fetch($entries)) {
			// We delete all entries that aren't associated with a worker entry after a given amount of days or retrials
			if (($entry['retrial'] > $max_retrial) || ($entry['received'] < DateTimeFormat::utc('now - ' . $expired_days . ' days'))) {
				self::deleteById($entry['id']);
			}
			if (!$entry['trust'] || !self::isProcessable($entry['id'])) {
				continue;
			}
			DI::logger()->debug('Process leftover entry', $entry);
			self::process($entry['id'], false);
		}
		DBA::close($entries);

		// Optimizing this table only last seconds
		if (DI::config()->get('system', 'optimize_tables')) {
			DI::logger()->info('Optimize start');
			DBA::optimizeTable('inbox-entry');
			DI::logger()->info('Optimize end');
		}
	}

	private static function retrial(int $id)
	{
		DBA::update('inbox-entry', ["`retrial` = `retrial` + 1"], ['id' => $id]);
	}

	public static function isProcessable(int $id): bool
	{
		$entry = DBA::selectFirst('inbox-entry', [], ['id' => $id]);
		if (empty($entry)) {
			return false;
		}

		if (($entry['type'] == 'as:Follow') && ($entry['object-type'] == 'as:Note')) {
			return true;
		}

		if (!empty($entry['object-id']) && Post::exists(['uri' => $entry['object-id']])) {
			// The object already exists, so processing can be done
			return true;
		}

		if (!empty($entry['context'])) {
			if (DBA::exists('post-thread', ['context-id' => ItemURI::getIdByURI($entry['context'], false)])) {
				// We have got the context in the system, so the post can be processed
				return true;
			}
		}

		if (!empty($entry['conversation'])) {
			if (DBA::exists('post-thread', ['conversation-id' => ItemURI::getIdByURI($entry['conversation'], false)])) {
				// We have got the conversation in the system, so the post can be processed
				return true;
			}
		}

		if (!empty($entry['object-id']) && !empty($entry['in-reply-to-id']) && ($entry['object-id'] != $entry['in-reply-to-id'])) {
			if (DBA::exists('inbox-entry', ['object-id' => $entry['in-reply-to-id']])) {
				// This entry belongs to some other entry that should be processed first
				self::retrial($id);
				return false;
			}
			if (!Processor::alreadyKnown($entry['in-reply-to-id'], '')) {
				// This entry belongs to some other entry that need to be fetched first
				if (Fetch::hasWorker($entry['in-reply-to-id'])) {
					DI::logger()->debug('Fetching of the activity is already queued', ['id' => $entry['activity-id'], 'reply-to-id' => $entry['in-reply-to-id']]);
					self::retrial($id);
					return false;
				}
				Fetch::add($entry['in-reply-to-id']);
				$activity = json_decode($entry['activity'], true);
				if (in_array($entry['in-reply-to-id'], $activity['children'] ?? [])) {
					DI::logger()->notice('reply-to-id is already in the list of children', ['id' => $entry['in-reply-to-id'], 'children' => $activity['children'], 'depth' => count($activity['children'])]);
					self::retrial($id);
					return false;
				}
				$activity['recursion-depth'] = 0;
				$activity['callstack']       = Processor::addToCallstack($activity['callstack'] ?? []);
				$wid                         = Worker::add(Worker::PRIORITY_HIGH, 'FetchMissingActivity', $entry['in-reply-to-id'], $activity, '', Receiver::COMPLETION_ASYNC);
				Fetch::setWorkerId($entry['in-reply-to-id'], $wid);
				DI::logger()->debug('Fetch missing activity', ['wid' => $wid, 'id' => $entry['activity-id'], 'reply-to-id' => $entry['in-reply-to-id']]);
				self::retrial($id);
				return false;
			}
		}

		return true;
	}

	/**
	 * Process all activities that are children of a given post url
	 *
	 * @param string $uri
	 * @param array  $parent
	 * @return int
	 */
	public static function processReplyByUri(string $uri, array $parent = []): int
	{
		$count   = 0;
		$entries = DBA::select('inbox-entry', ['id'], ["`in-reply-to-id` = ? AND `object-id` != ?", $uri, $uri]);
		while ($entry = DBA::fetch($entries)) {
			$count += 1;
			self::process($entry['id'], false, $parent);
		}
		DBA::close($entries);
		return $count;
	}

	/**
	 * Checks if there are children of the given uri
	 *
	 * @param string $uri
	 *
	 * @return bool
	 */
	public static function hasChildren(string $uri): bool
	{
		return DBA::exists('inbox-entry', ["`in-reply-to-id` = ? AND `object-id` != ?", $uri, $uri]);
	}

	/**
	 * Prepare the queue entry.
	 * This is a test function that is used solely for development.
	 *
	 * @param integer $id
	 * @return array
	 */
	public static function reprepareActivityById(int $id): array
	{
		$entry = DBA::selectFirst('inbox-entry', [], ['id' => $id]);
		if (empty($entry)) {
			return [];
		}

		$receiver = DBA::selectFirst('inbox-entry-receiver', ['uid'], ['queue-id' => $id]);
		if (!empty($receiver)) {
			$uid = $receiver['uid'];
		} else {
			$uid = 0;
		}

		$trust_source = $entry['trust'];

		$data     = json_decode($entry['activity'], true);
		$activity = json_decode($data['raw'], true);

		$ldactivity = JsonLD::compact($activity);
		return [
			'data'  => Receiver::prepareObjectData($ldactivity, $uid, $entry['push'], $trust_source),
			'trust' => $trust_source
		];
	}

	/**
	 * Set the trust for all untrusted entries.
	 * This is a test function that is used solely for development.
	 *
	 * @return void
	 */
	public static function reprepareAll()
	{
		$entries = DBA::select('inbox-entry', ['id'], ["NOT `trust` AND `wid` IS NULL"], ['order' => ['id' => true]]);
		while ($entry = DBA::fetch($entries)) {
			$data = self::reprepareActivityById($entry['id']);
			if ($data['trust']) {
				DBA::update('inbox-entry', ['trust' => true], ['id' => $entry['id']]);
			}
		}
		DBA::close($entries);
	}
}
