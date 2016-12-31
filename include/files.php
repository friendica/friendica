<?php
function create_files_from_item($itemid) {
	$a = get_app();

	$messages = q("SELECT `guid`, `uid`, `id`, `edited`, `deleted`, `file`, `parent` FROM `item` WHERE `id` = %d LIMIT 1", intval($itemid));

	if (!$messages)
		return;

	$message = $messages[0];

	// Clean up all tags
	q("DELETE FROM `term` WHERE `otype` = %d AND `oid` = %d AND `type` IN (%d, %d)",
		intval(TERM_OBJ_POST),
		intval($itemid),
		intval(TERM_FILE),
		intval(TERM_CATEGORY));

	if ($message["deleted"])
		return;

	if (preg_match_all("/\[(.*?)\]/ism", $message["file"], $files))
		foreach ($files[1] as $file)
			$r = q("INSERT INTO `term` (`uid`, `oid`, `otype`, `type`, `term`) VALUES (%d, %d, %d, %d, '%s')",
				intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_FILE), dbesc($file));

	if (preg_match_all("/\<(.*?)\>/ism", $message["file"], $files))
		foreach ($files[1] as $file)
			$r = q("INSERT INTO `term` (`uid`, `oid`, `otype`, `type`, `term`) VALUES (%d, %d, %d, %d, '%s')",
				intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_CATEGORY), dbesc($file));
}

function create_files_from_itemuri($itemuri, $uid) {
	$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

	if(count($messages)) {
		foreach ($messages as $message)
			create_files_from_item($message["id"]);
	}
}

function update_files_for_items() {
	$messages = q("SELECT `id` FROM `item` where file !=''");

	foreach ($messages as $message) {
		echo $message["id"]."\n";
		create_files_from_item($message["id"]);
	}
}
?>
