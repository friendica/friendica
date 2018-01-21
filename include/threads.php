<?php

use Friendica\Database\DBM;

function add_thread($itemid, $onlyshadow = false) {
	$items = q("SELECT `uid`, `created`, `edited`, `commented`, `received`, `changed`, `wall`, `private`, `pubmail`,
			`moderated`, `visible`, `spam`, `starred`, `bookmark`, `contact-id`, `gcontact-id`,
			`deleted`, `origin`, `forum_mode`, `mention`, `network`, `author-id`, `owner-id`
		FROM `item` WHERE `id` = %d AND (`parent` = %d OR `parent` = 0) LIMIT 1", intval($itemid), intval($itemid));

	if (!$items)
		return;

	$item = $items[0];
	$item['iid'] = $itemid;

	if (!$onlyshadow) {
		$result = dba::insert('thread', $item);

		logger("Add thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);
	}
}

function update_thread_uri($itemuri, $uid) {
	$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

	if (DBM::isResult($messages)) {
		foreach ($messages as $message) {
			update_thread($message["id"]);
		}
	}
}

function update_thread($itemid, $setmention = false) {
	$items = q("SELECT `uid`, `guid`, `title`, `body`, `created`, `edited`, `commented`, `received`, `changed`, `wall`, `private`, `pubmail`, `moderated`, `visible`, `spam`, `starred`, `bookmark`, `contact-id`, `gcontact-id`,
			`deleted`, `origin`, `forum_mode`, `network`, `rendered-html`, `rendered-hash` FROM `item` WHERE `id` = %d AND (`parent` = %d OR `parent` = 0) LIMIT 1", intval($itemid), intval($itemid));

	if (!DBM::isResult($items)) {
		return;
	}

	$item = $items[0];

	if ($setmention) {
		$item["mention"] = 1;
	}

	$sql = "";

	foreach ($item AS $field => $data)
		if (!in_array($field, ["guid", "title", "body", "rendered-html", "rendered-hash"])) {
			if ($sql != "") {
				$sql .= ", ";
			}

			$sql .= "`".$field."` = '".dbesc($data)."'";
		}

	$result = q("UPDATE `thread` SET ".$sql." WHERE `iid` = %d", intval($itemid));

	logger("Update thread for item ".$itemid." - guid ".$item["guid"]." - ".print_r($result, true)." ".print_r($item, true), LOGGER_DEBUG);

	// Updating a shadow item entry
	$items = q("SELECT `id` FROM `item` WHERE `guid` = '%s' AND `uid` = 0 LIMIT 1", dbesc($item["guid"]));

	if (!DBM::isResult($items)) {
		return;
	}

	$result = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `rendered-html` = '%s', `rendered-hash` = '%s' WHERE `id` = %d",
			dbesc($item["title"]),
			dbesc($item["body"]),
			dbesc($item["rendered-html"]),
			dbesc($item["rendered-hash"]),
			intval($items[0]["id"])
		);
	logger("Updating public shadow for post ".$items[0]["id"]." - guid ".$item["guid"]." Result: ".print_r($result, true), LOGGER_DEBUG);
}

function delete_thread_uri($itemuri, $uid) {
	$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

	if (DBM::isResult($messages)) {
		foreach ($messages as $message) {
			delete_thread($message["id"], $itemuri);
		}
	}
}

function delete_thread($itemid, $itemuri = "") {
	$item = q("SELECT `uid` FROM `thread` WHERE `iid` = %d", intval($itemid));

	if (!DBM::isResult($item)) {
		logger('No thread found for id '.$itemid, LOGGER_DEBUG);
		return;
	}

	// Using dba::delete at this time could delete the associated item entries
	$result = dba::e("DELETE FROM `thread` WHERE `iid` = ?", $itemid);

	logger("delete_thread: Deleted thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);

	if ($itemuri != "") {
		$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' AND NOT `deleted` AND NOT (`uid` IN (%d, 0))",
				dbesc($itemuri),
				intval($item["uid"])
			);
		if (!DBM::isResult($r)) {
			dba::delete('item', ['uri' => $itemuri, 'uid' => 0]);
			logger("delete_thread: Deleted shadow for item ".$itemuri, LOGGER_DEBUG);
		}
	}
}
