<?php
/**
 * @file include/spool_post.php
 * @brief Posts items that wer spooled because they couldn't be posted.
 */

use \Friendica\Core\Config;

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

	Config::load();

	$path = get_spoolpath();

	if (is_writable($path)){
		if ($dh = opendir($path)) {
			while (($file = readdir($dh)) !== false) {
				$fullfile = $path."/".$file;
				if (filetype($fullfile) != "file") {
					continue;
				}
				$arr = json_decode(file_get_contents($fullfile), true);

				// If it isn't an array then it is no spool file
				if (!is_array($arr)) {
					continue;
				}

				// Skip if it doesn't seem to be an item array
				if (!isset($arr['uid']) AND !isset($arr['uri']) AND !isset($arr['network'])) {
					continue;
				}

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
