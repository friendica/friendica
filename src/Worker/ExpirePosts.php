<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
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
		$tables = ['item', 'post', 'post-content', 'post-thread', 'post-thread-user'];
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
		$userposts = DBA::select('post-user', [], ["`uri-id` not in (select `uri-id` from `post`)"]);
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

		$rows      = 0;
		$userposts = DBA::select('post-user', [], ["`gravity` = ? AND `id` not in (select `post-user-id` from `post-thread-user`)", Item::GRAVITY_PARENT]);
		while ($fields = DBA::fetch($userposts)) {
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
		$limit = DI::config()->get('system', 'dbclean-expire-limit');
		if (empty($limit)) {
			return;
		}

		// We have to avoid deleting newly created "item-uri" entries.
		// So we fetch a post that had been stored yesterday and only delete older ones.
		$item = Post::selectFirstThread(
			['uri-id'],
			["`uid` = ? AND `received` < ?", 0, DateTimeFormat::utc('now - 1 day')],
			['order' => ['received' => true]]
		);
		if (empty($item['uri-id'])) {
			DI::logger()->warning('No item with uri-id found - we better quit here');
			return;
		}
		DI::logger()->notice('Start collecting orphaned URI-ID', ['last-id' => $item['uri-id']]);

		$sql = [
			'SELECT i.id
			FROM `item-uri` i
			LEFT JOIN `post-user` pu1 ON i.id = pu1.`uri-id`
			LEFT JOIN `post-user` pu2 ON i.id = pu2.`parent-uri-id`
			LEFT JOIN `post-user` pu3 ON i.id = pu3.`thr-parent-id`
			LEFT JOIN `post-user` pu4 ON i.id = pu4.`external-id`
			LEFT JOIN `post-user` pu5 ON i.id = pu5.`replies-id`
			LEFT JOIN `post-thread` pt1 ON i.id = pt1.`context-id`
			LEFT JOIN `post-thread` pt2 ON i.id = pt2.`conversation-id`
			LEFT JOIN `mail` m1 ON i.id = m1.`uri-id`
			LEFT JOIN `event` e ON i.id = e.`uri-id`
			LEFT JOIN `user-contact` uc ON i.id = uc.`uri-id`
			LEFT JOIN `contact` c ON i.id = c.`uri-id`
			LEFT JOIN `apcontact` ac ON i.id = ac.`uri-id`
			LEFT JOIN `diaspora-contact` dc ON i.id = dc.`uri-id`
			LEFT JOIN `inbox-status` ins ON i.id = ins.`uri-id`
			LEFT JOIN `post-delivery` pd1 ON i.id = pd1.`uri-id`
			LEFT JOIN `post-delivery` pd2 ON i.id = pd2.`inbox-id`
			LEFT JOIN `mail` m2 ON i.id = m2.`parent-uri-id`
			LEFT JOIN `mail` m3 ON i.id = m3.`thr-parent-id`
			WHERE
			  i.id < ? AND
			  pu1.`uri-id` IS NULL AND
			  pu2.`parent-uri-id` IS NULL AND
			  pu3.`thr-parent-id` IS NULL AND
			  pu4.`external-id` IS NULL AND
			  pu5.`replies-id` IS NULL AND
			  pt1.`context-id` IS NULL AND
			  pt2.`conversation-id` IS NULL AND
			  m1.`uri-id` IS NULL AND
			  e.`uri-id` IS NULL AND
			  uc.`uri-id` IS NULL AND
			  c.`uri-id` IS NULL AND
			  ac.`uri-id` IS NULL AND
			  dc.`uri-id` IS NULL AND
			  ins.`uri-id` IS NULL AND
			  pd1.`uri-id` IS NULL AND
			  pd2.`inbox-id` IS NULL AND
			  m2.`parent-uri-id` IS NULL AND
			  m3.`thr-parent-id` IS NULL
			LIMIT ?',
			$item['uri-id'],
			$limit
		];
		$pass = 0;
		do {
			++$pass;
			$uris  = DBA::p(...$sql);
			$total = DBA::numRows($uris);
			DI::logger()->notice('Start deleting orphaned URI-ID', ['pass' => $pass, 'last-id' => $item['uri-id']]);
			$affected_count = 0;
			while ($rows = DBA::toArray($uris, false, 100)) {
				$ids = array_column($rows, 'id');
				DBA::delete('item-uri', ['id' => $ids]);
				$affected_count += DBA::affectedRows();
				DI::logger()->debug('Deleted', ['pass' => $pass, 'affected_count' => $affected_count, 'total' => $total]);
			}
			DBA::close($uris);
			DBA::commit();
			DI::logger()->notice('Orphaned URI-ID entries removed', ['pass' => $pass, 'rows' => $affected_count]);
		} while ($affected_count);
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
				AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-media`
					WHERE `uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` INNER JOIN `contact` ON `contact`.`id` = `contact-id` AND `notify_new_posts`
					WHERE `parent-uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user`
					WHERE (`origin` OR `event-id` != 0 OR `post-type` = ?) AND `parent-uri-id` = `post-thread`.`uri-id`)
				AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-content`
					WHERE `resource-id` != 0 AND `uri-id` = `post-thread`.`uri-id`)",
				DateTimeFormat::utc('now - ' . (int)$expire_days . ' days'), Item::PT_PERSONAL_NOTE
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
					AND `i`.`parent-uri-id` = `post-user`.`uri-id` AND `i`.`received` > ?)",
				Item::GRAVITY_PARENT, 0, DateTimeFormat::utc('now - ' . (int)$expire_days_unclaimed . ' days'), 0, 0, DateTimeFormat::utc('now - ' . (int)$expire_days_unclaimed . ' days')
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
