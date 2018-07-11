<?php
/**
 * @file src/Database/PostUpdate.php
 */
namespace Friendica\Database;

use Friendica\Core\Config;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use dba;

require_once 'include/dba.php';

/**
 * Post update functions
 */
class PostUpdate
{
	/**
	 * @brief Calls the post update functions
	 */
	public static function update()
	{
		if (!self::update1194()) {
			return;
		}
		if (!self::update1198()) {
			return;
		}
		if (!self::update1206()) {
			return;
		}
//		if (!self::update1276()) {
//			return;
//		}
	}

	/**
	 * @brief Updates the "global" field in the item table
	 *
	 * @return bool "true" when the job is done
	 */
	private static function update1194()
	{
		// Was the script completed?
		if (Config::get("system", "post_update_version") >= 1194) {
			return true;
		}

		logger("Start", LOGGER_DEBUG);

		$end_id = Config::get("system", "post_update_1194_end");
		if (!$end_id) {
			$r = q("SELECT `id` FROM `item` WHERE `uid` != 0 ORDER BY `id` DESC LIMIT 1");
			if ($r) {
				Config::set("system", "post_update_1194_end", $r[0]["id"]);
				$end_id = Config::get("system", "post_update_1194_end");
			}
		}

		logger("End ID: ".$end_id, LOGGER_DEBUG);

		$start_id = Config::get("system", "post_update_1194_start");

		$query1 = "SELECT `item`.`id` FROM `item` ";

		$query2 = "INNER JOIN `item` AS `shadow` ON `item`.`uri` = `shadow`.`uri` AND `shadow`.`uid` = 0 ";

		$query3 = "WHERE `item`.`uid` != 0 AND `item`.`id` >= %d AND `item`.`id` <= %d
				AND `item`.`visible` AND NOT `item`.`private`
				AND NOT `item`.`deleted` AND NOT `item`.`moderated`
				AND `item`.`network` IN ('%s', '%s', '%s', '')
				AND `item`.`allow_cid` = '' AND `item`.`allow_gid` = ''
				AND `item`.`deny_cid` = '' AND `item`.`deny_gid` = ''
				AND NOT `item`.`global`";

		$r = q($query1.$query2.$query3."  ORDER BY `item`.`id` LIMIT 1",
			intval($start_id), intval($end_id),
			dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS));
		if (!$r) {
			Config::set("system", "post_update_version", 1194);
			logger("Update is done", LOGGER_DEBUG);
			return true;
		} else {
			Config::set("system", "post_update_1194_start", $r[0]["id"]);
			$start_id = Config::get("system", "post_update_1194_start");
		}

		logger("Start ID: ".$start_id, LOGGER_DEBUG);

		$r = q($query1.$query2.$query3."  ORDER BY `item`.`id` LIMIT 1000,1",
			intval($start_id), intval($end_id),
			dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS));
		if ($r) {
			$pos_id = $r[0]["id"];
		} else {
			$pos_id = $end_id;
		}
		logger("Progress: Start: ".$start_id." position: ".$pos_id." end: ".$end_id, LOGGER_DEBUG);

		q("UPDATE `item` ".$query2." SET `item`.`global` = 1 ".$query3,
			intval($start_id), intval($pos_id),
			dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS));

		logger("Done", LOGGER_DEBUG);
	}

	/**
	 * @brief set the author-id and owner-id in all item entries
	 *
	 * This job has to be started multiple times until all entries are set.
	 * It isn't started in the update function since it would consume too much time and can be done in the background.
	 *
	 * @return bool "true" when the job is done
	 */
	private static function update1198()
	{
		// Was the script completed?
		if (Config::get("system", "post_update_version") >= 1198) {
			return true;
		}

		logger("Start", LOGGER_DEBUG);

		// Check if the first step is done (Setting "author-id" and "owner-id" in the item table)
		$fields = ['author-link', 'author-name', 'author-avatar', 'owner-link', 'owner-name', 'owner-avatar', 'network', 'uid'];
		$r = dba::select('item', $fields, ['author-id' => 0, 'owner-id' => 0], ['limit' => 1000]);
		if (!$r) {
			// Are there unfinished entries in the thread table?
			$r = q("SELECT COUNT(*) AS `total` FROM `thread`
				INNER JOIN `item` ON `item`.`id` =`thread`.`iid`
				WHERE `thread`.`author-id` = 0 AND `thread`.`owner-id` = 0 AND
					(`thread`.`uid` IN (SELECT `uid` from `user`) OR `thread`.`uid` = 0)");

			if ($r && ($r[0]["total"] == 0)) {
				Config::set("system", "post_update_version", 1198);
				logger("Done", LOGGER_DEBUG);
				return true;
			}

			// Update the thread table from the item table
			$r = q("UPDATE `thread` INNER JOIN `item` ON `item`.`id`=`thread`.`iid`
					SET `thread`.`author-id` = `item`.`author-id`,
					`thread`.`owner-id` = `item`.`owner-id`
				WHERE `thread`.`author-id` = 0 AND `thread`.`owner-id` = 0 AND
					(`thread`.`uid` IN (SELECT `uid` from `user`) OR `thread`.`uid` = 0)");

			logger("Updated threads", LOGGER_DEBUG);
			if (DBM::is_result($r)) {
				Config::set("system", "post_update_version", 1198);
				logger("Done", LOGGER_DEBUG);
				return true;
			}
			return false;
		}

		logger("Query done", LOGGER_DEBUG);

		$item_arr = [];
		foreach ($r as $item) {
			$index = $item["author-link"]."-".$item["owner-link"]."-".$item["uid"];
			$item_arr[$index] = ["author-link" => $item["author-link"],
							"owner-link" => $item["owner-link"],
							"uid" => $item["uid"]];
		}

		// Set the "author-id" and "owner-id" in the item table and add a new public contact entry if needed
		foreach ($item_arr as $item) {
			$default = ['url' => $item['author-link'], 'name' => $item['author-name'],
				'photo' => $item['author-avatar'], 'network' => $item['network']];
			$author_id = Contact::getIdForURL($item["author-link"], 0, false, $default);

			$default = ['url' => $item['owner-link'], 'name' => $item['owner-name'],
				'photo' => $item['owner-avatar'], 'network' => $item['network']];
			$owner_id = Contact::getIdForURL($item["owner-link"], 0, false, $default);

			if ($author_id == 0) {
				$author_id = -1;
			}
			if ($owner_id == 0) {
				$owner_id = -1;
			}
			dba::update('item', ['author-id' => $author_id, 'owner-id' => $owner_id], ['uid' => $item['uid'], 'author-link' => $item['author-link'], 'owner-link' => $item['owner-link'], 'author-id' => 0, 'owner-id' => 0]);
		}

		logger("Updated items", LOGGER_DEBUG);
		return false;
	}

	/**
	 * @brief update the "last-item" field in the "self" contact
	 *
	 * This field avoids cost intensive calls in the admin panel and in "nodeinfo"
	 *
	 * @return bool "true" when the job is done
	 */
	private static function update1206()
	{
		// Was the script completed?
		if (Config::get("system", "post_update_version") >= 1206) {
			return true;
		}

		logger("Start", LOGGER_DEBUG);
		$r = q("SELECT `contact`.`id`, `contact`.`last-item`,
			(SELECT MAX(`changed`) FROM `item` USE INDEX (`uid_wall_changed`) WHERE `wall` AND `uid` = `user`.`uid`) AS `lastitem_date`
			FROM `user`
			INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`");

		if (!DBM::is_result($r)) {
			return false;
		}
		foreach ($r as $user) {
			if (!empty($user["lastitem_date"]) && ($user["lastitem_date"] > $user["last-item"])) {
				dba::update('contact', ['last-item' => $user['lastitem_date']], ['id' => $user['id']]);
			}
		}

		Config::set("system", "post_update_version", 1206);
		logger("Done", LOGGER_DEBUG);
		return true;
	}

	/**
	 * @brief update the item related tables
	 *
	 * @return bool "true" when the job is done
	 */
	private static function update1276()
	{
		// Was the script completed?
		if (Config::get("system", "post_update_version") >= 1276) {
			return true;
		}

		logger("Start", LOGGER_DEBUG);

		$fields = array_merge(Item::MIXED_CONTENT_FIELDLIST, ['network', 'author-id', 'owner-id', 'tag', 'file',
			'author-name', 'author-avatar', 'author-link', 'owner-name', 'owner-avatar', 'owner-link', 'id']);

		$id = Config::get("system", "post_update_version_1276_id", 0);

		$start_id = $id;
		$rows = 0;
		$condition = ["`id` > ?", $id];
		$params = ['order' => ['id'], 'limit' => 10000];
		$items = Item::select($fields, $condition, $params);
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

			Item::update($item, ['id' => $id]);

			++$rows;
		}
		dba::close($items);

		Config::set("system", "post_update_version_1276_id", $id);

		logger("Processed rows: " . $rows, LOGGER_DEBUG);

		if ($start_id == $id) {
			Config::set("system", "post_update_version", 1275);
			logger("Done", LOGGER_DEBUG);
			return true;
		}

		return false;
	}
}
