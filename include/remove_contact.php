<?php
/**
 * @file include/remove_contact.php
 * @brief Removes orphaned data from deleted contacts
 */
require_once("boot.php");

function remove_contact_run($argv, $argc) {
	global $a, $db;

	if (is_null($a)) {
		$a = new App;
	}

	if (is_null($db)) {
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	}

	load_config('config');
	load_config('system');

	if ($argc != 2) {
		return;
	}

	$id = intval($argv[1]);

	// Only delete if the contact doesn't exist (anymore)
	$r = q("SELECT `id` FROM `contact` WHERE `id` = %d", intval($id));
	if (dbm::is_result($r)) {
		return;
	}

	q("DELETE FROM `item` WHERE `contact-id` = %d", intval($id));

	q("DELETE FROM `photo` WHERE `contact-id` = %d", intval($id));

	q("DELETE FROM `mail` WHERE `contact-id` = %d", intval($id));

	q("DELETE FROM `event` WHERE `cid` = %d", intval($id));

	q("DELETE FROM `queue` WHERE `cid` = %d", intval($id));
}

if (array_search(__file__, get_included_files()) === 0) {
	remove_contact_run($_SERVER["argv"], $_SERVER["argc"]);
	killme();
}
?>
