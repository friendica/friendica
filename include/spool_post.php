<?php
/**
 * @file include/spool_post.php
 * @brief Posts items that wer spooled because they couldn't be posted.
 */
require_once("boot.php");
require_once("include/items.php");

function spool_post_run($argv, $argc) {
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

	$path = get_spoolpath();

	if (is_writable($path)){
		if ($dh = opendir($path)) {
			while (($file = readdir($dh)) !== false) {
				$fullfile = $path."/".$file;
				if (filetype($fullfile) != "file") {
					continue;
				}
				$arr = json_decode(file_get_contents($fullfile), true);
				$result = item_store($arr);
				logger("Spool file ".$file." stored: ".$result, LOGGER_DEBUG);
				unlink($fullfile);
			}
			closedir($dh);
		}
	}
}

if (array_search(__file__, get_included_files()) === 0) {
	spool_post_run($_SERVER["argv"], $_SERVER["argc"]);
	killme();
}
?>
