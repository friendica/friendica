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

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
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
		self::deleteExpiredOriginPosts();

		self::deleteOrphanedEntries();

		self::deleteUnusedItemUri();

		self::deleteExpiredExternalPosts();

		if (DI::config()->get('system', 'add_missing_posts')) {
			self::addMissingEntries();
		}

		// Set the expiry for origin posta
		Worker::add(Worker::PRIORITY_LOW, 'Expire');

		// update nodeinfo data after everything is cleaned up
		Worker::add(Worker::PRIORITY_LOW, 'NodeInfo');
	}

	/**
	 * Delete expired origin posts and orphaned post related table entries
	 *
	 * @return void
	 */
	private static function deleteExpiredOriginPosts()
	{
		Logger::notice('Delete expired posts');
		// physically remove anything that has been deleted for more than two months
		$condition = ["`gravity` = ? AND `deleted` AND `changed` < ?", Item::GRAVITY_PARENT, DateTimeFormat::utc('now - 60 days')];
		$rows = Post::select(['guid', 'uri-id', 'uid'],  $condition);
		while ($row = Post::fetch($rows)) {
			Logger::info('Delete expired item', ['uri-id' => $row['uri-id'], 'guid' => $row['guid']]);
			Post\User::delete(['parent-uri-id' => $row['uri-id'], 'uid' => $row['uid']]);
		}
		DBA::close($rows);

		Logger::notice('Delete expired posts - done');
	}

	/**
	 * Delete orphaned entries in the post related tables
	 *
	 * @return void
	 */
	private static function deleteOrphanedEntries()
	{
		Logger::notice('Delete orphaned entries');

		// "post-user" is the leading table. So we delete every entry that isn't found there
		$tables = ['item', 'post', 'post-content', 'post-thread', 'post-thread-user'];
		foreach ($tables as $table) {
			if (($table == 'item') && !DBStructure::existsTable('item')) {
				continue;
			}

			Logger::notice('Start collecting orphaned entries', ['table' => $table]);
			$uris = DBA::select($table, ['uri-id'], ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user`)"]);
			$affected_count = 0;
			Logger::notice('Deleting orphaned entries - start', ['table' => $table]);
			while ($rows = DBA::toArray($uris, false, 100)) {
				$ids = array_column($rows, 'uri-id');
				DBA::delete($table, ['uri-id' => $ids]);
				$affected_count += DBA::affectedRows();
			}
			DBA::close($uris);
			Logger::notice('Orphaned entries deleted', ['table' => $table, 'rows' => $affected_count]);
		}
		Logger::notice('Delete orphaned entries - done');
	}

	/**
	 * Add missing entries in some post related tables
	 *
	 * @return void
	 */
	private static function addMissingEntries()
	{
		Logger::notice('Adding missing entries');

		$rows = 0;
		$userposts = DBA::select('post-user', [], ["`uri-id` not in (select `uri-id` from `post`)"]);
		while ($fields = DBA::fetch($userposts)) {
			$post_fields = DI::dbaDefinition()->truncateFieldsForTable('post', $fields);
			DBA::insert('post', $post_fields, Database::INSERT_IGNORE);
			$rows++;
		}
		DBA::close($userposts);
		if ($rows > 0) {
			Logger::notice('Added post entries', ['rows' => $rows]);
		} else {
			Logger::notice('No post entries added');
		}

		$rows = 0;
		$userposts = DBA::select('post-user', [], ["`gravity` = ? AND `uri-id` not in (select `uri-id` from `post-thread`)", Item::GRAVITY_PARENT]);
		while ($fields = DBA::fetch($userposts)) {
			$post_fields = DI::dbaDefinition()->truncateFieldsForTable('post-thread', $fields);
			$post_fields['commented'] = $post_fields['changed'] = $post_fields['created'];
			DBA::insert('post-thread', $post_fields, Database::INSERT_IGNORE);
			$rows++;
		}
		DBA::close($userposts);
		if ($rows > 0) {
			Logger::notice('Added post-thread entries', ['rows' => $rows]);
		} else {
			Logger::notice('No post-thread entries added');
		}

		$rows = 0;
		$userposts = DBA::select('post-user', [], ["`gravity` = ? AND `id` not in (select `post-user-id` from `post-thread-user`)", Item::GRAVITY_PARENT]);
		while ($fields = DBA::fetch($userposts)) {
			$post_fields = DI::dbaDefinition()->truncateFieldsForTable('post-thread-user', $fields);
			$post_fields['commented'] = $post_fields['changed'] = $post_fields['created'];
			DBA::insert('post-thread-user', $post_fields, Database::INSERT_IGNORE);
			$rows++;
		}
		DBA::close($userposts);
		if ($rows > 0) {
			Logger::notice('Added post-thread-user entries', ['rows' => $rows]);
		} else {
			Logger::notice('No post-thread-user entries added');
		}
	}

	/**
	 * Delete unused item-uri entries
	 */
	private static function deleteUnusedItemUri()
	{
		// We have to avoid deleting newly created "item-uri" entries.
		// So we fetch a post that had been stored yesterday and only delete older ones.
		$item = Post::selectFirstThread(['uri-id'], ["`uid` = ? AND `received` < ?", 0, DateTimeFormat::utc('now - 1 day')],
			['order' => ['received' => true]]);
		if (empty($item['uri-id'])) {
			Logger::warning('No item with uri-id found - we better quit here');
			return;
		}
		Logger::notice('Start collecting orphaned URI-ID', ['last-id' => $item['uri-id']]);
		$uris = DBA::select('item-uri', ['id'], ["`id` < ?
			AND NOT EXISTS(SELECT `uri-id` FROM `post-user` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `parent-uri-id` FROM `post-user` WHERE `parent-uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `thr-parent-id` FROM `post-user` WHERE `thr-parent-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `external-id` FROM `post-user` WHERE `external-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `conversation-id` FROM `post-thread` WHERE `conversation-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `mail` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `event` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `user-contact` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `contact` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `apcontact` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `diaspora-contact` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `inbox-status` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `post-delivery` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `uri-id` FROM `post-delivery` WHERE `inbox-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `parent-uri-id` FROM `mail` WHERE `parent-uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `thr-parent-id` FROM `mail` WHERE `thr-parent-id` = `item-uri`.`id`)", $item['uri-id']]);

		Logger::notice('Start deleting orphaned URI-ID', ['last-id' => $item['uri-id']]);
		$affected_count = 0;
		while ($rows = DBA::toArray($uris, false, 100)) {
			$ids = array_column($rows, 'id');
			DBA::delete('item-uri', ['id' => $ids]);
			$affected_count += DBA::affectedRows();
			Logger::info('Deleted', ['rows' => $affected_count]);
		}
		DBA::close($uris);
		Logger::notice('Orphaned URI-ID entries removed', ['rows' => $affected_count]);
	}

	/**
	 * Delete old external post entries
	 */
	private static function deleteExpiredExternalPosts()
	{
		$expire_days = DI::config()->get('system', 'dbclean-expire-days');
		$expire_days_unclaimed = DI::config()->get('system', 'dbclean-expire-unclaimed');
		if (empty($expire_days_unclaimed)) {
			$expire_days_unclaimed = $expire_days;
		}

		$limit = DI::config()->get('system', 'dbclean-expire-limit');
		if (empty($limit)) {
			return;
		}

		if (!empty($expire_days)) {
			Logger::notice('Start collecting expired threads', ['expiry_days' => $expire_days]);
			$uris = DBA::select('item-uri', ['id'], ["`id` IN
				(SELECT `uri-id` FROM `post-thread` WHERE `received` < ?
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
						WHERE `resource-id` != 0 AND `uri-id` = `post-thread`.`uri-id`))",
			    DateTimeFormat::utc('now - ' . (int)$expire_days . ' days'), Item::PT_PERSONAL_NOTE]);

			Logger::notice('Start deleting expired threads');
			$affected_count = 0;
			while ($rows = DBA::toArray($uris, false, 100)) {
				$ids = array_column($rows, 'id');
				DBA::delete('item-uri', ['id' => $ids]);
				$affected_count += DBA::affectedRows();
			}
			DBA::close($uris);

			Logger::notice('Deleted expired threads', ['rows' => $affected_count]);
		}

		if (!empty($expire_days_unclaimed)) {
			Logger::notice('Start collecting unclaimed public items', ['expiry_days' => $expire_days_unclaimed]);
			$uris = DBA::select('item-uri', ['id'], ["`id` IN
				(SELECT `uri-id` FROM `post-user` WHERE `gravity` = ? AND `uid` = ? AND `received` < ?
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` != ?
						AND `i`.`parent-uri-id` = `post-user`.`uri-id`)
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` = ?
						AND `i`.`parent-uri-id` = `post-user`.`uri-id` AND `i`.`received` > ?))",
				Item::GRAVITY_PARENT, 0, DateTimeFormat::utc('now - ' . (int)$expire_days_unclaimed . ' days'), 0, 0, DateTimeFormat::utc('now - ' . (int)$expire_days_unclaimed . ' days')]);

			Logger::notice('Start deleting unclaimed public items');
			$affected_count = 0;
			while ($rows = DBA::toArray($uris, false, 100)) {
				$ids = array_column($rows, 'id');
				DBA::delete('item-uri', ['id' => $ids]);
				$affected_count += DBA::affectedRows();
			}
			DBA::close($uris);
			Logger::notice('Deleted unclaimed public items', ['rows' => $affected_count]);
		}
	}
}
