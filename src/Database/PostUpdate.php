<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Database;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\PermissionSet;
use Friendica\Model\UserItem;

/**
 * These database-intensive post update routines are meant to be executed in the background by the cronjob.
 *
 * If there is a need for a intensive migration after a database structure change, update this file
 * by adding a new method at the end with the number of the new DB_UPDATE_VERSION.
 */
class PostUpdate
{
	/**
	 * Calls the post update functions
	 */
	public static function update()
	{
		if (!self::update1194()) {
			return false;
		}
		if (!self::update1206()) {
			return false;
		}
		if (!self::update1279()) {
			return false;
		}
		if (!self::update1281()) {
			return false;
		}
		if (!self::update1297()) {
			return false;
		}
		if (!self::update1322()) {
			return false;
		}
		if (!self::update1329()) {
			return false;
		}

		return true;
	}

	/**
	 * Updates the "global" field in the item table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1194()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1194) {
			return true;
		}

		Logger::log("Start", Logger::DEBUG);

		$end_id = DI::config()->get("system", "post_update_1194_end");
		if (!$end_id) {
			$r = q("SELECT `id` FROM `item` WHERE `uid` != 0 ORDER BY `id` DESC LIMIT 1");
			if ($r) {
				DI::config()->set("system", "post_update_1194_end", $r[0]["id"]);
				$end_id = DI::config()->get("system", "post_update_1194_end");
			}
		}

		Logger::log("End ID: ".$end_id, Logger::DEBUG);

		$start_id = DI::config()->get("system", "post_update_1194_start");

		$query1 = "SELECT `item`.`id` FROM `item` ";

		$query2 = "INNER JOIN `item` AS `shadow` ON `item`.`uri` = `shadow`.`uri` AND `shadow`.`uid` = 0 ";

		$query3 = "WHERE `item`.`uid` != 0 AND `item`.`id` >= %d AND `item`.`id` <= %d
				AND `item`.`visible` AND NOT `item`.`private`
				AND NOT `item`.`deleted` AND NOT `item`.`moderated`
				AND `item`.`network` IN ('%s', '%s', '%s', '')
				AND NOT `item`.`global`";

		$r = q($query1.$query2.$query3."  ORDER BY `item`.`id` LIMIT 1",
			intval($start_id), intval($end_id),
			DBA::escape(Protocol::DFRN), DBA::escape(Protocol::DIASPORA), DBA::escape(Protocol::OSTATUS));
		if (!$r) {
			DI::config()->set("system", "post_update_version", 1194);
			Logger::log("Update is done", Logger::DEBUG);
			return true;
		} else {
			DI::config()->set("system", "post_update_1194_start", $r[0]["id"]);
			$start_id = DI::config()->get("system", "post_update_1194_start");
		}

		Logger::log("Start ID: ".$start_id, Logger::DEBUG);

		$r = q($query1.$query2.$query3."  ORDER BY `item`.`id` LIMIT 1000,1",
			intval($start_id), intval($end_id),
			DBA::escape(Protocol::DFRN), DBA::escape(Protocol::DIASPORA), DBA::escape(Protocol::OSTATUS));
		if ($r) {
			$pos_id = $r[0]["id"];
		} else {
			$pos_id = $end_id;
		}
		Logger::log("Progress: Start: ".$start_id." position: ".$pos_id." end: ".$end_id, Logger::DEBUG);

		q("UPDATE `item` ".$query2." SET `item`.`global` = 1 ".$query3,
			intval($start_id), intval($pos_id),
			DBA::escape(Protocol::DFRN), DBA::escape(Protocol::DIASPORA), DBA::escape(Protocol::OSTATUS));

		Logger::log("Done", Logger::DEBUG);
	}

	/**
	 * update the "last-item" field in the "self" contact
	 *
	 * This field avoids cost intensive calls in the admin panel and in "nodeinfo"
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1206()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1206) {
			return true;
		}

		Logger::log("Start", Logger::DEBUG);
		$r = q("SELECT `contact`.`id`, `contact`.`last-item`,
			(SELECT MAX(`changed`) FROM `item` USE INDEX (`uid_wall_changed`) WHERE `wall` AND `uid` = `user`.`uid`) AS `lastitem_date`
			FROM `user`
			INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`");

		if (!DBA::isResult($r)) {
			return false;
		}
		foreach ($r as $user) {
			if (!empty($user["lastitem_date"]) && ($user["lastitem_date"] > $user["last-item"])) {
				DBA::update('contact', ['last-item' => $user['lastitem_date']], ['id' => $user['id']]);
			}
		}

		DI::config()->set("system", "post_update_version", 1206);
		Logger::log("Done", Logger::DEBUG);
		return true;
	}

	/**
	 * update the item related tables
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1279()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1279) {
			return true;
		}

		$id = DI::config()->get("system", "post_update_version_1279_id", 0);

		Logger::log("Start from item " . $id, Logger::DEBUG);

		$fields = array_merge(Item::MIXED_CONTENT_FIELDLIST, ['network', 'author-id', 'owner-id', 'tag', 'file',
			'author-name', 'author-avatar', 'author-link', 'owner-name', 'owner-avatar', 'owner-link', 'id',
			'uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'psid', 'post-type', 'bookmark', 'type',
			'inform', 'postopts', 'icid']);

		$start_id = $id;
		$rows = 0;
		$condition = ["`id` > ?", $id];
		$params = ['order' => ['id'], 'limit' => 10000];
		$items = Item::select($fields, $condition, $params);

		if (DBA::errorNo() != 0) {
			Logger::log('Database error ' . DBA::errorNo() . ':' . DBA::errorMessage());
			return false;
		}

		while ($item = Item::fetch($items)) {
			$id = $item['id'];

			if (empty($item['author-id'])) {
				$default = ['url' => $item['author-link'], 'name' => $item['author-name'],
					'photo' => $item['author-avatar'], 'network' => $item['network']];

				$item['author-id'] = Contact::getIdForURL($item["author-link"], 0, false, $default);
			}

			if (empty($item['owner-id'])) {
				$default = ['url' => $item['owner-link'], 'name' => $item['owner-name'],
					'photo' => $item['owner-avatar'], 'network' => $item['network']];

				$item['owner-id'] = Contact::getIdForURL($item["owner-link"], 0, false, $default);
			}

			if (empty($item['psid'])) {
				$item['psid'] = PermissionSet::getIdFromACL(
					$item['uid'],
					$item['allow_cid'],
					$item['allow_gid'],
					$item['deny_cid'],
					$item['deny_gid'],
					false
				);
			}

			$item['allow_cid'] = null;
			$item['allow_gid'] = null;
			$item['deny_cid'] = null;
			$item['deny_gid'] = null;

			if ($item['post-type'] == 0) {
				if (!empty($item['type']) && ($item['type'] == 'note')) {
					$item['post-type'] = Item::PT_PERSONAL_NOTE;
				} elseif (!empty($item['type']) && ($item['type'] == 'photo')) {
					$item['post-type'] = Item::PT_IMAGE;
				} elseif (!empty($item['bookmark']) && $item['bookmark']) {
					$item['post-type'] = Item::PT_PAGE;
				}
			}

			self::createLanguage($item);

			if (!empty($item['icid']) && !empty($item['language'])) {
				DBA::update('item-content', ['language' => $item['language']], ['id' => $item['icid']]);
			}
			unset($item['language']);

			Item::update($item, ['id' => $id]);

			++$rows;
		}
		DBA::close($items);

		DI::config()->set("system", "post_update_version_1279_id", $id);

		Logger::log("Processed rows: " . $rows . " - last processed item:  " . $id, Logger::DEBUG);

		if ($start_id == $id) {
			// Set all deprecated fields to "null" if they contain an empty string
			$nullfields = ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'postopts', 'inform', 'type',
				'bookmark', 'file', 'location', 'coord', 'tag', 'plink', 'title', 'content-warning',
				'body', 'app', 'verb', 'object-type', 'object', 'target-type', 'target',
				'author-name', 'author-link', 'author-avatar', 'owner-name', 'owner-link', 'owner-avatar',
				'rendered-hash', 'rendered-html'];
			foreach ($nullfields as $field) {
				$fields = [$field => null];
				$condition = [$field => ''];
				Logger::log("Setting '" . $field . "' to null if empty.", Logger::DEBUG);
				// Important: This has to be a "DBA::update", not a "Item::update"
				DBA::update('item', $fields, $condition);
			}

			DI::config()->set("system", "post_update_version", 1279);
			Logger::log("Done", Logger::DEBUG);
			return true;
		}

		return false;
	}

	private static function createLanguage(&$item)
	{
		if (empty($item['postopts'])) {
			return;
		}

		$opts = explode(',', $item['postopts']);

		$postopts = [];

		foreach ($opts as $opt) {
			if (strstr($opt, 'lang=')) {
				$language = substr($opt, 5);
			} else {
				$postopts[] = $opt;
			}
		}

		if (empty($language)) {
			return;
		}

		if (!empty($postopts)) {
			$item['postopts'] = implode(',', $postopts);
		} else {
			$item['postopts'] = null;
		}

		$lang_pairs = explode(':', $language);

		$lang_arr = [];

		foreach ($lang_pairs as $pair) {
			$lang_pair_arr = explode(';', $pair);
			if (count($lang_pair_arr) == 2) {
				$lang_arr[$lang_pair_arr[0]] = $lang_pair_arr[1];
			}
		}

		$item['language'] = json_encode($lang_arr);
	}

	/**
	 * update item-uri data. Prerequisite for the next item structure update.
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1281()
	{
		// Was the script completed?
		if (DI::config()->get("system", "post_update_version") >= 1281) {
			return true;
		}

		$id = DI::config()->get("system", "post_update_version_1281_id", 0);

		Logger::log("Start from item " . $id, Logger::DEBUG);

		$fields = ['id', 'guid', 'uri', 'uri-id', 'parent-uri', 'parent-uri-id', 'thr-parent', 'thr-parent-id'];

		$start_id = $id;
		$rows = 0;
		$condition = ["`id` > ?", $id];
		$params = ['order' => ['id'], 'limit' => 10000];
		$items = DBA::select('item', $fields, $condition, $params);

		if (DBA::errorNo() != 0) {
			Logger::log('Database error ' . DBA::errorNo() . ':' . DBA::errorMessage());
			return false;
		}

		while ($item = DBA::fetch($items)) {
			$id = $item['id'];

			if (empty($item['uri'])) {
				// Should not happen
				continue;
			} elseif (empty($item['uri-id'])) {
				$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);
			}

			if (empty($item['parent-uri'])) {
				$item['parent-uri-id'] = $item['uri-id'];
			} elseif (empty($item['parent-uri-id'])) {
				$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);
			}

			// Very old items don't have this field
			if (empty($item['thr-parent'])) {
				$item['thr-parent-id'] = $item['parent-uri-id'];
			} elseif (empty($item['thr-parent-id'])) {
				$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);
			}

			unset($item['id']);
			unset($item['guid']);
			unset($item['uri']);
			unset($item['parent-uri']);
			unset($item['thr-parent']);

			DBA::update('item', $item, ['id' => $id]);

			++$rows;
		}
		DBA::close($items);

		DI::config()->set("system", "post_update_version_1281_id", $id);

		Logger::log("Processed rows: " . $rows . " - last processed item:  " . $id, Logger::DEBUG);

		if ($start_id == $id) {
			Logger::log("Updating item-uri in item-activity", Logger::DEBUG);
			DBA::e("UPDATE `item-activity` INNER JOIN `item-uri` ON `item-uri`.`uri` = `item-activity`.`uri` SET `item-activity`.`uri-id` = `item-uri`.`id` WHERE `item-activity`.`uri-id` IS NULL");

			Logger::log("Updating item-uri in item-content", Logger::DEBUG);
			DBA::e("UPDATE `item-content` INNER JOIN `item-uri` ON `item-uri`.`uri` = `item-content`.`uri` SET `item-content`.`uri-id` = `item-uri`.`id` WHERE `item-content`.`uri-id` IS NULL");

			DI::config()->set("system", "post_update_version", 1281);
			Logger::log("Done", Logger::DEBUG);
			return true;
		}

		return false;
	}

	/**
	 * Set the delivery queue count to a negative value for all items preceding the feature.
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1297()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1297) {
			return true;
		}

		$max_item_delivery_data = DBA::selectFirst('item-delivery-data', ['iid'], ['queue_count > 0 OR queue_done > 0'], ['order' => ['iid']]);
		$max_iid = $max_item_delivery_data['iid'];

		Logger::info('Start update1297 with max iid: ' . $max_iid);

		$condition = ['`queue_count` = 0 AND `iid` < ?', $max_iid];

		DBA::update('item-delivery-data', ['queue_count' => -1], $condition);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error ' . DBA::errorNo() . ':' . DBA::errorMessage());
			return false;
		}

		Logger::info('Processed rows: ' . DBA::affectedRows());

		DI::config()->set('system', 'post_update_version', 1297);

		Logger::info('Done');

		return true;
	}
	/**
	 * Remove contact duplicates
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1322()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1322) {
			return true;
		}

		Logger::info('Start');

		$contacts = DBA::p("SELECT `nurl`, `uid` FROM `contact`
			WHERE EXISTS (SELECT `nurl` FROM `contact` AS `c2`
				WHERE `c2`.`nurl` = `contact`.`nurl` AND `c2`.`id` != `contact`.`id` AND `c2`.`uid` = `contact`.`uid` AND `c2`.`network` IN (?, ?, ?) AND NOT `deleted`)
			AND (`network` IN (?, ?, ?) OR (`uid` = ?)) AND NOT `deleted` GROUP BY `nurl`, `uid`",
			Protocol::DIASPORA, Protocol::OSTATUS, Protocol::ACTIVITYPUB,
			Protocol::DIASPORA, Protocol::OSTATUS, Protocol::ACTIVITYPUB, 0);

		while ($contact = DBA::fetch($contacts)) {
			Logger::info('Remove duplicates', ['nurl' => $contact['nurl'], 'uid' => $contact['uid']]);
			Contact::removeDuplicates($contact['nurl'], $contact['uid']);
		}

		DBA::close($contact);
		DI::config()->set('system', 'post_update_version', 1322);

		Logger::info('Done');

		return true;
	}

	/**
	 * update user-item data with notifications
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1329()
	{
		// Was the script completed?
		if (DI::config()->get('system', 'post_update_version') >= 1329) {
			return true;
		}

		$id = DI::config()->get('system', 'post_update_version_1329_id', 0);

		Logger::info('Start', ['item' => $id]);

		$start_id = $id;
		$rows = 0;
		$condition = ["`id` > ?", $id];
		$params = ['order' => ['id'], 'limit' => 10000];
		$items = DBA::select('item', ['id'], $condition, $params);

		if (DBA::errorNo() != 0) {
			Logger::error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			$id = $item['id'];

			UserItem::setNotification($item['id']);

			++$rows;
		}
		DBA::close($items);

		DI::config()->set('system', 'post_update_version_1329_id', $id);

		Logger::info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::config()->set('system', 'post_update_version', 1329);
			Logger::info('Done');
			return true;
		}

		return false;
	}
}
