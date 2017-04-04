<?php
/**
 * @file include/post_update.php
 */

/**
 * @brief Calls the post update functions
 */
function post_update() {

	if (!post_update_1192()) {
		return;
	}
	if (!post_update_1194()) {
		return;
	}
	if (!post_update_1198()) {
		return;
	}
	if (!post_update_1206()) {
		return;
	}
}

/**
 * @brief set the gcontact-id in all item entries
 *
 * This job has to be started multiple times until all entries are set.
 * It isn't started in the update function since it would consume too much time and can be done in the background.
 *
 * @return bool "true" when the job is done
 */
function post_update_1192() {

	// Was the script completed?
	if (get_config("system", "post_update_version") >= 1192)
		return true;

	// Check if the first step is done (Setting "gcontact-id" in the item table)
	$r = q("SELECT `author-link`, `author-name`, `author-avatar`, `uid`, `network` FROM `item` WHERE `gcontact-id` = 0 LIMIT 1000");
	if (!$r) {
		// Are there unfinished entries in the thread table?
		$r = q("SELECT COUNT(*) AS `total` FROM `thread`
			INNER JOIN `item` ON `item`.`id` =`thread`.`iid`
			WHERE `thread`.`gcontact-id` = 0 AND
				(`thread`.`uid` IN (SELECT `uid` from `user`) OR `thread`.`uid` = 0)");

		if ($r AND ($r[0]["total"] == 0)) {
			set_config("system", "post_update_version", 1192);
			return true;
		}

		// Update the thread table from the item table
		q("UPDATE `thread` INNER JOIN `item` ON `item`.`id`=`thread`.`iid`
				SET `thread`.`gcontact-id` = `item`.`gcontact-id`
			WHERE `thread`.`gcontact-id` = 0 AND
				(`thread`.`uid` IN (SELECT `uid` from `user`) OR `thread`.`uid` = 0)");

		return false;
	}

	$item_arr = array();
	foreach ($r AS $item) {
		$index = $item["author-link"]."-".$item["uid"];
		$item_arr[$index] = array("author-link" => $item["author-link"],
						"uid" => $item["uid"],
						"network" => $item["network"]);
	}

	// Set the "gcontact-id" in the item table and add a new gcontact entry if needed
	foreach ($item_arr AS $item) {
		$gcontact_id = get_gcontact_id(array("url" => $item['author-link'], "network" => $item['network'],
						"photo" => $item['author-avatar'], "name" => $item['author-name']));
		q("UPDATE `item` SET `gcontact-id` = %d WHERE `uid` = %d AND `author-link` = '%s' AND `gcontact-id` = 0",
			intval($gcontact_id), intval($item["uid"]), dbesc($item["author-link"]));
	}
	return false;
}

/**
 * @brief Updates the "global" field in the item table
 *
 * @return bool "true" when the job is done
 */
function post_update_1194() {

	// Was the script completed?
	if (get_config("system", "post_update_version") >= 1194)
		return true;

	logger("Start", LOGGER_DEBUG);

	$end_id = get_config("system", "post_update_1194_end");
	if (!$end_id) {
		$r = q("SELECT `id` FROM `item` WHERE `uid` != 0 ORDER BY `id` DESC LIMIT 1");
		if ($r) {
			set_config("system", "post_update_1194_end", $r[0]["id"]);
			$end_id = get_config("system", "post_update_1194_end");
		}
	}

	logger("End ID: ".$end_id, LOGGER_DEBUG);

	$start_id = get_config("system", "post_update_1194_start");

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
		set_config("system", "post_update_version", 1194);
		logger("Update is done", LOGGER_DEBUG);
		return true;
	} else {
		set_config("system", "post_update_1194_start", $r[0]["id"]);
		$start_id = get_config("system", "post_update_1194_start");
	}

	logger("Start ID: ".$start_id, LOGGER_DEBUG);

	$r = q($query1.$query2.$query3."  ORDER BY `item`.`id` LIMIT 1000,1",
		intval($start_id), intval($end_id),
		dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_OSTATUS));
	if ($r)
		$pos_id = $r[0]["id"];
	else
		$pos_id = $end_id;

	logger("Progress: Start: ".$start_id." position: ".$pos_id." end: ".$end_id, LOGGER_DEBUG);

	$r = q("UPDATE `item` ".$query2." SET `item`.`global` = 1 ".$query3,
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
function post_update_1198() {

	// Was the script completed?
	if (get_config("system", "post_update_version") >= 1198)
		return true;

	logger("Start", LOGGER_DEBUG);

	// Check if the first step is done (Setting "author-id" and "owner-id" in the item table)
	$r = q("SELECT `author-link`, `owner-link`, `uid` FROM `item` WHERE `author-id` = 0 AND `owner-id` = 0 LIMIT 100");
	if (!$r) {
		// Are there unfinished entries in the thread table?
		$r = q("SELECT COUNT(*) AS `total` FROM `thread`
			INNER JOIN `item` ON `item`.`id` =`thread`.`iid`
			WHERE `thread`.`author-id` = 0 AND `thread`.`owner-id` = 0 AND
				(`thread`.`uid` IN (SELECT `uid` from `user`) OR `thread`.`uid` = 0)");

		if ($r AND ($r[0]["total"] == 0)) {
			set_config("system", "post_update_version", 1198);
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
		if (dbm::is_result($r)) {
			set_config("system", "post_update_version", 1198);
			logger("Done", LOGGER_DEBUG);
			return true;
		}
		return false;
	}

	logger("Query done", LOGGER_DEBUG);

	$item_arr = array();
	foreach ($r AS $item) {
		$index = $item["author-link"]."-".$item["owner-link"]."-".$item["uid"];
		$item_arr[$index] = array("author-link" => $item["author-link"],
						"owner-link" => $item["owner-link"],
						"uid" => $item["uid"]);
	}

	// Set the "gcontact-id" in the item table and add a new gcontact entry if needed
	foreach ($item_arr AS $item) {
		$author_id = get_contact($item["author-link"], 0);
		$owner_id = get_contact($item["owner-link"], 0);

		if ($author_id == 0)
			$author_id = -1;

		if ($owner_id == 0)
			$owner_id = -1;

		q("UPDATE `item` SET `author-id` = %d, `owner-id` = %d
			WHERE `uid` = %d AND `author-link` = '%s' AND `owner-link` = '%s'
				AND `author-id` = 0 AND `owner-id` = 0",
			intval($author_id), intval($owner_id), intval($item["uid"]),
			dbesc($item["author-link"]), dbesc($item["owner-link"]));
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
function post_update_1206() {
	// Was the script completed?
	if (get_config("system", "post_update_version") >= 1206)
		return true;

	logger("Start", LOGGER_DEBUG);
	$r = q("SELECT `contact`.`id`, `contact`.`last-item`,
		(SELECT MAX(`changed`) FROM `item` USE INDEX (`uid_wall_changed`) WHERE `wall` AND `uid` = `user`.`uid`) AS `lastitem_date`
		FROM `user`
		INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`");

	if (!dbm::is_result($r)) {
		return false;
	}
	foreach ($r AS $user) {
		if (!empty($user["lastitem_date"]) AND ($user["lastitem_date"] > $user["last-item"])) {
			q("UPDATE `contact` SET `last-item` = '%s' WHERE `id` = %d",
				dbesc($user["lastitem_date"]),
				intval($user["id"]));
		}
	}

	set_config("system", "post_update_version", 1206);
	logger("Done", LOGGER_DEBUG);
	return true;
}

?>
