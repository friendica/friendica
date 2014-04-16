<?php
function add_thread($itemid) {
	$items = q("SELECT `uid`, `created`, `edited`, `commented`, `received`, `changed`, `wall`, `private`, `pubmail`, `moderated`, `visible`, `spam`, `starred`, `bookmark`, `contact-id`,
			`deleted`, `origin`, `forum_mode`, `mention`, `network`  FROM `item` WHERE `id` = %d AND (`parent` = %d OR `parent` = 0) LIMIT 1", intval($itemid), intval($itemid));

	if (!$items)
		return;

	$item = $items[0];
	$item['iid'] = $itemid;

	$result = dbq("INSERT INTO `thread` (`"
			.implode("`, `", array_keys($item))
			."`) VALUES ('"
			.implode("', '", array_values($item))
			."')" );

	logger("add_thread: Add thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);
}

function update_thread_uri($itemuri, $uid) {
	$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

	if(count($messages))
		foreach ($messages as $message)
			update_thread($message["id"]);
}

function update_thread($itemid, $setmention = false) {
	$items = q("SELECT `uid`, `created`, `edited`, `commented`, `received`, `changed`, `wall`, `private`, `pubmail`, `moderated`, `visible`, `spam`, `starred`, `bookmark`, `contact-id`,
			`deleted`, `origin`, `forum_mode`, `network`  FROM `item` WHERE `id` = %d AND (`parent` = %d OR `parent` = 0) LIMIT 1", intval($itemid), intval($itemid));

	if (!$items)
		return;

	$item = $items[0];

	if ($setmention)
		$item["mention"] = 1;

	$sql = "";

	foreach ($item AS $field => $data) {
		if ($sql != "")
			$sql .= ", ";

		$sql .= "`".$field."` = '".$data."'";
	}

	$result = q("UPDATE `thread` SET ".$sql." WHERE `iid` = %d", $itemid);

	logger("update_thread: Update thread for item ".$itemid." - ".print_r($result, true)." ".print_r($item, true), LOGGER_DEBUG);
}

function delete_thread_uri($itemuri, $uid) {
	$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

	if(count($messages))
		foreach ($messages as $message)
			delete_thread($message["id"]);
}

function delete_thread($itemid) {
	$result = q("DELETE FROM `thread` WHERE `iid` = %d", intval($itemid));

	logger("delete_thread: Deleted thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);
}

function update_threads() {
        global $db;

	logger("update_threads: start");

	$messages = $db->q("SELECT `id` FROM `item` WHERE `id` = `parent`", true);

	logger("update_threads: fetched messages: ".count($messages));

	while ($message = $db->qfetch())
		add_thread($message["id"]);
	$db->qclose();
}

function update_threads_mention() {
	$a = get_app();

	$users = q("SELECT `uid`, `nickname` FROM `user` ORDER BY `uid`");

	foreach ($users AS $user) {
		$self = normalise_link($a->get_baseurl() . '/profile/' . $user['nickname']);
		$selfhttps = str_replace("http://", "https://", $self);
		$parents = q("SELECT DISTINCT(`parent`) FROM `item` WHERE `uid` = %d AND
				((`owner-link` IN ('%s', '%s')) OR (`author-link` IN ('%s', '%s')))",
				$user["uid"], $self, $selfhttps, $self, $selfhttps);

		foreach ($parents AS $parent)
			q("UPDATE `thread` SET `mention` = 1 WHERE `iid` = %d", $parent["parent"]);
	}
}
?>
