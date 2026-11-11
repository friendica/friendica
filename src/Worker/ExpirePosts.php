<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Util\DateTimeFormat;

class ExpirePosts
{
	/**
	 * Expire posts and remove unused item-uri entries
	 *
	 * @return void
	 */
	public static function execute()
	{
		DI::logger()->notice('Expire posts - start');

		if (!DBA::acquireOptimizeLock()) {
			DI::logger()->warning('Lock could not be acquired');
			Worker::defer();
			return;
		}

		$processlist = DBA::processlist();
		if ($processlist['max_time'] > 60) {
			DI::logger()->warning('Processlist shows a long running query, task will be deferred.', ['time' => $processlist['max_time'], 'query' => $processlist['max_query']]);
			Worker::defer();
			return;
		}

		DI::logger()->notice('Expire posts - Delete expired origin posts');
		self::deleteExpiredOriginPosts();

		DI::logger()->notice('Expire posts - Delete orphaned entries');
		self::deleteOrphanedEntries();

		DI::logger()->notice('Expire posts - delete external posts');
		self::deleteExpiredExternalPosts();

		if (DI::config()->get('system', 'add_missing_posts')) {
			DI::logger()->notice('Expire posts - add missing posts');
			self::addMissingEntries();
		}

		DI::logger()->notice('Expire posts - delete unused attachments');
		self::deleteUnusedAttachments();

		DI::logger()->notice('Expire posts - delete unused item-uri entries');
		self::deleteUnusedItemUri();

		DBA::releaseOptimizeLock();
		DI::logger()->notice('Expire posts - done');
	}

	/**
	 * Delete expired origin posts and orphaned post related table entries
	 *
	 * @return void
	 */
	private static function deleteExpiredOriginPosts()
	{
		$limit = DI::config()->get('system', 'dbclean-expire-limit');
		if (empty($limit)) {
			return;
		}

		DI::logger()->notice('Delete expired posts');
		// physically remove anything that has been deleted for more than two months
		$condition = ["`gravity` = ? AND `deleted` AND `edited` < ?", Item::GRAVITY_PARENT, DateTimeFormat::utc('now - 60 days')];
		$pass      = 0;
		do {
			++$pass;
			$rows           = DBA::select('post-user', ['uri-id', 'uid'], $condition, ['limit' => $limit]);
			$affected_count = 0;
			while ($row = Post::fetch($rows)) {
				DI::logger()->info('Delete expired item', ['pass' => $pass, 'uri-id' => $row['uri-id']]);
				Post\User::delete(['parent-uri-id' => $row['uri-id'], 'uid' => $row['uid']]);
				$affected_count += DBA::affectedRows();
				Post\Origin::delete(['parent-uri-id' => $row['uri-id'], 'uid' => $row['uid']]);
				$affected_count += DBA::affectedRows();
			}
			DBA::close($rows);
			DBA::commit();
			DI::logger()->notice('Delete expired posts - done', ['pass' => $pass, 'rows' => $affected_count]);
		} while ($affected_count);
	}

	/**
	 * Delete orphaned entries in the post related tables
	 *
	 * @return void
	 */
	private static function deleteOrphanedEntries()
	{
		DI::logger()->notice('Delete orphaned entries');

		// "post-user" is the leading table. So we delete every entry that isn't found there
		$tables = ['item', 'post', 'post-content', 'post-quote', 'post-thread', 'post-thread-user'];
		foreach ($tables as $table) {
			if (($table == 'item') && !DBStructure::existsTable('item')) {
				continue;
			}

			DI::logger()->notice('Start collecting orphaned entries', ['table' => $table]);
			$uris           = DBA::select($table, ['uri-id'], ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user`)"]);
			$affected_count = 0;
			DI::logger()->notice('Deleting orphaned entries - start', ['table' => $table]);
			while ($rows = DBA::toArray($uris, false, 100)) {
				$ids = array_column($rows, 'uri-id');
				DBA::delete($table, ['uri-id' => $ids]);
				$affected_count += DBA::affectedRows();
			}
			DBA::close($uris);
			DBA::commit();
			DI::logger()->notice('Orphaned entries deleted', ['table' => $table, 'rows' => $affected_count]);
		}
		DI::logger()->notice('Delete orphaned entries - done');
	}

	/**
	 * Add missing entries in some post related tables
	 *
	 * @return void
	 */
	private static function addMissingEntries()
	{
		DI::logger()->notice('Adding missing entries');

		$rows      = 0;
		$userposts = DBA::p("SELECT `post-user`.* FROM `post-user` LEFT JOIN `post` ON `post`.`uri-id` = `post-user`.`uri-id` WHERE `post`.`uri-id` IS NULL");
		while ($fields = DBA::fetch($userposts)) {
			$post_fields = DI::dbaDefinition()->truncateFieldsForTable('post', $fields);
			DBA::insert('post', $post_fields, Database::INSERT_IGNORE);
			$rows++;
		}
		DBA::close($userposts);
		if ($rows > 0) {
			DI::logger()->notice('Added post entries', ['rows' => $rows]);
		} else {
			DI::logger()->notice('No post entries added');
		}

		$rows      = 0;
		$userposts = DBA::select('post-user', [], ["`gravity` = ? AND `uri-id` not in (select `uri-id` from `post-thread`)", Item::GRAVITY_PARENT]);
		while ($fields = DBA::fetch($userposts)) {
			$post_fields              = DI::dbaDefinition()->truncateFieldsForTable('post-thread', $fields);
			$post_fields['commented'] = $post_fields['changed'] = $post_fields['created'];
			DBA::insert('post-thread', $post_fields, Database::INSERT_IGNORE);
			$rows++;
		}
		DBA::close($userposts);
		if ($rows > 0) {
			DI::logger()->notice('Added post-thread entries', ['rows' => $rows]);
		} else {
			DI::logger()->notice('No post-thread entries added');
		}

		// Entries that exist but lack the link to "post-user"
		DBA::e("UPDATE `post-thread-user` INNER JOIN `post-user` ON `post-user`.`uri-id` = `post-thread-user`.`uri-id` AND `post-user`.`uid` = `post-thread-user`.`uid`
			SET `post-thread-user`.`post-user-id` = `post-user`.`id` WHERE `post-thread-user`.`post-user-id` IS NULL");

		$rows      = 0;
		$userposts = DBA::p("SELECT `post-user`.* FROM `post-user` WHERE `gravity` = ? AND NOT EXISTS
			(SELECT 1 FROM `post-thread-user` WHERE `post-thread-user`.`uri-id` = `post-user`.`uri-id` AND `post-thread-user`.`uid` = `post-user`.`uid`)", Item::GRAVITY_PARENT);
		while ($fields = DBA::fetch($userposts)) {
			$fields['post-user-id']   = $fields['id'];
			$post_fields              = DI::dbaDefinition()->truncateFieldsForTable('post-thread-user', $fields);
			$post_fields['commented'] = $post_fields['changed'] = $post_fields['created'];
			DBA::insert('post-thread-user', $post_fields, Database::INSERT_IGNORE);
			$rows++;
		}
		DBA::close($userposts);
		if ($rows > 0) {
			DI::logger()->notice('Added post-thread-user entries', ['rows' => $rows]);
		} else {
			DI::logger()->notice('No post-thread-user entries added');
		}
	}

	/**
	 * Delete unused item-uri entries
	 */
	private static function deleteUnusedItemUri()
	{
		$limit = (int) DI::config()->get('system', 'dbclean-expire-limit');
		if ($limit == 0) {
			return;
		}

		// We have to avoid deleting newly created "item-uri" entries.
		// So we fetch a post that had been stored yesterday and only delete older ones.
		$item = Post::selectFirstThread(
			['uri-id'],
			["`uid` = ? AND `received` < ?", 0, DateTimeFormat::utc('now - 1 day')],
			['order' => ['received' => true]],
		);
		if (empty($item['uri-id'])) {
			DI::logger()->warning('No item with uri-id found - we better quit here');
			return;
		}
		DI::logger()->notice('Start collecting orphaned URI-ID', ['last-id' => $item['uri-id']]);

		$affected_count = 0;
		for ($start = 0; $start < $item['uri-id']; $start += $limit) {
			$end  = min($start + $limit, $item['uri-id'] - 1);
			$rows = self::getUnusedItemUriIds($start, $end);
			if (empty($rows)) {
				continue;
			}

			$deleted = 0;
			foreach (array_chunk($rows, 100) as $ids) {
				DBA::delete('item-uri', ['id' => $ids]);
				$deleted += DBA::affectedRows();
			}
			DBA::commit();

			$affected_count += $deleted;
			DI::logger()->debug('Deleted', ['start' => $start, 'end' => $end, 'deleted' => $deleted, 'affected_count' => $affected_count]);
		}
		DI::logger()->notice('Orphaned URI-ID entries removed', ['rows' => $affected_count]);
	}

	/**
	 * Fetch the ids of unused item-uri entries in the given id range.
	 *
	 * The range should be small, so that the query is short.
	 * Long running queries are holding metadata locks that can block other processes.
	 *
	 * @return int[] ids of unused item-uri entries
	 */
	private static function getUnusedItemUriIds(int $start, int $end): array
	{
		// Fields that are referencing "item-uri" entries that we must not delete
		$references = [
			['post-user', 'uri-id'],
			['post-user', 'parent-uri-id'],
			['post-user', 'thr-parent-id'],
			['post-user', 'external-id'],
			['post-user', 'replies-id'],
			['post-thread', 'context-id'],
			['post-thread', 'conversation-id'],
			['post-quote', 'quote-uri-id'],
			['contact', 'uri-id'],
			['apcontact', 'uri-id'],
			['diaspora-contact', 'uri-id'],
			['user-contact', 'uri-id'],
			['inbox-status', 'uri-id'],
			['post-delivery', 'uri-id'],
			['post-delivery', 'inbox-id'],
			['event', 'uri-id'],
			['mail', 'uri-id'],
			['mail', 'parent-uri-id'],
			['mail', 'thr-parent-id'],
		];

		$condition = '';
		foreach ($references as $reference) {
			$condition .= ' AND NOT EXISTS (SELECT 1 FROM ' . DBA::buildTableString([$reference[0]]) . ' WHERE ' . DBA::quoteIdentifier($reference[1]) . ' = `item-uri`.`id`)';
		}

		$sql = 'SELECT `id` FROM `item-uri` WHERE `id` > ? AND `id` <= ?' . $condition;

		return array_column(DBA::toArray(DBA::p($sql, $start, $end)), 'id');
	}

	/**
	 * Delete old external post entries
	 */
	private static function deleteExpiredExternalPosts()
	{
		$limit = DI::config()->get('system', 'dbclean-expire-limit');
		if (empty($limit)) {
			return;
		}

		$expire_days           = DI::config()->get('system', 'dbclean-expire-days');
		$expire_days_unclaimed = DI::config()->get('system', 'dbclean-expire-unclaimed');
		if (empty($expire_days_unclaimed)) {
			$expire_days_unclaimed = $expire_days;
		}

		if (!empty($expire_days)) {
			DI::logger()->notice('Start collecting expired threads', ['expiry_days' => $expire_days]);
			$condition = [
				"`received` < ?
				AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-thread-user`
					WHERE (`mention` OR `starred` OR `wall`) AND `uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-category`
					WHERE `uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-collection`
					WHERE `uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` INNER JOIN `contact` ON `contact`.`id` = `contact-id` AND `notify_new_posts`
					WHERE `parent-uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user`
					WHERE (`origin` OR `event-id` != 0 OR `post-type` = ?) AND `parent-uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-content`
					WHERE `resource-id` != 0 AND `uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `post`.`parent-uri-id` FROM `post-quote`
					INNER JOIN `post` ON `post`.`uri-id` = `post-quote`.`uri-id`
					INNER JOIN `post-origin` ON `post-origin`.`uri-id` = `post-quote`.`quote-uri-id`
					WHERE `post`.`parent-uri-id` = `post-thread`.`uri-id`)
				AND NOT EXISTS (SELECT 1 FROM `contact` WHERE `contact`.`uri-id` = `post-thread`.`uri-id`)",
				DateTimeFormat::utc('now - ' . (int) $expire_days . ' days'), Item::PT_PERSONAL_NOTE,
			];
			$pass = 0;
			do {
				++$pass;
				$uris = DBA::select('post-thread', ['uri-id'], $condition, ['limit' => $limit]);

				DI::logger()->notice('Start deleting expired threads', ['pass' => $pass]);
				$affected_count = 0;
				while ($rows = DBA::toArray($uris, false, 100)) {
					$ids = array_column($rows, 'uri-id');
					DBA::delete('item-uri', ['id' => $ids]);
					$affected_count += DBA::affectedRows();
				}
				DBA::close($uris);
				DBA::commit();
				DI::logger()->notice('Deleted expired threads', ['pass' => $pass, 'rows' => $affected_count]);
			} while ($affected_count);
		}

		if (!empty($expire_days_unclaimed)) {
			DI::logger()->notice('Start collecting unclaimed public items', ['expiry_days' => $expire_days_unclaimed]);
			$condition = [
				"`gravity` = ? AND `uid` = ? AND `received` < ?
				AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` != ?
					AND `i`.`parent-uri-id` = `post-user`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` = ?
					AND `i`.`parent-uri-id` = `post-user`.`uri-id` AND `i`.`received` > ?)
				AND NOT `uri-id` IN (SELECT `post`.`parent-uri-id` FROM `post-quote`
					INNER JOIN `post` ON `post`.`uri-id` = `post-quote`.`uri-id`
					INNER JOIN `post-origin` ON `post-origin`.`uri-id` = `post-quote`.`quote-uri-id`
					WHERE `post`.`parent-uri-id` = `post-user`.`uri-id`)
				AND NOT EXISTS (SELECT 1 FROM `contact` WHERE `contact`.`uri-id` = `post-user`.`uri-id`)",
				Item::GRAVITY_PARENT, 0, DateTimeFormat::utc('now - ' . (int) $expire_days_unclaimed . ' days'), 0, 0, DateTimeFormat::utc('now - ' . (int) $expire_days_unclaimed . ' days'),
			];
			$pass = 0;
			do {
				++$pass;
				$uris  = DBA::select('post-user', ['uri-id'], $condition, ['limit' => $limit]);
				$total = DBA::numRows($uris);
				DI::logger()->notice('Start deleting unclaimed public items', ['pass' => $pass]);
				$affected_count = 0;
				while ($rows = DBA::toArray($uris, false, 100)) {
					$ids = array_column($rows, 'uri-id');
					DBA::delete('item-uri', ['id' => $ids]);
					$affected_count += DBA::affectedRows();
					DI::logger()->debug('Deleted', ['pass' => $pass, 'affected_count' => $affected_count, 'total' => $total]);
				}
				DBA::close($uris);
				DBA::commit();
				DI::logger()->notice('Deleted unclaimed public items', ['pass' => $pass, 'rows' => $affected_count]);
			} while ($affected_count);
		}
	}

	/**
	 * Delete media attachments (excluding photos) that aren't linked to any post
	 *
	 * @return void
	 */
	private static function deleteUnusedAttachments()
	{
		$postmedia = DBA::select('attach', ['id'], ["`id` NOT IN (SELECT `attach-id` FROM `post-media`)"]);
		while ($media = DBA::fetch($postmedia)) {
			Attach::delete(['id' => $media['id']]);
		}
	}
}
