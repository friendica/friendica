<?php
/**
 * @file include/create_shadowentry.php
 * @brief This script creates posts with UID = 0 for a given public post.
 *
 * This script is started from mod/item.php to save some time when doing a post.
 */
require_once("boot.php");
require_once("include/threads.php");

function create_shadowentry_run($argv, $argc) {
	global $a, $db;

	if (is_null($a))
		$a = new App;

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

	$message_id = intval($argv[1]);

	add_shadow_entry($message_id);
}

if (array_search(__file__,get_included_files())===0){
  create_shadowentry_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
?>
