<?php
/**
 * @file include/remove_contact.php
 * @brief Removes orphaned data from deleted contacts
 */

use Friendica\Core\Config;

function remove_contact_run($argv, $argc) {
	if ($argc != 2) {
		return;
	}

	$id = intval($argv[1]);

	// Only delete if the contact doesn't exist (anymore)
	$r = dba::select('contact', array('id'), array('id' => $id), array('limit' => 1));
	if (dbm::is_result($r)) {
		return;
	}

	// Now we delete all the depending table entries
	dba::delete('contact', array('id' => $id));
}
