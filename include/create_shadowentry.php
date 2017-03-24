<?php
/**
 * @file include/create_shadowentry.php
 * @brief This script creates posts with UID = 0 for a given public post.
 *
 * This script is started from mod/item.php to save some time when doing a post.
 */

require_once("include/threads.php");

function create_shadowentry_run($argv, $argc) {
	if ($argc != 2) {
		return;
	}

	$message_id = intval($argv[1]);

	add_shadow_entry($message_id);
}
?>
