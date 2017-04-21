<?php
/**
 * @file include/remove_contact.php
 * @brief Removes orphaned data from deleted contacts
 */

use \Friendica\Core\Config;

function remove_contact_run($argv, $argc) {
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
?>
