<?php
/**
 * @file include/dbclean.php
 * @brief The script is called from time to time to clean the database entries and remove orphaned data.
 */
require_once("boot.php");

function dbclean_run(&$argv, &$argc) {
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

	if ($argc == 2) {
		$stage = intval($argv[1]);
	} else {
		$stage = 0;
	}
	remove_orphans($stage);
}

/**
 * @brief Remove orphaned database entries
 */
function remove_orphans($stage = 0) {
	global $db;

	if (($stage == 1) OR ($stage == 0)) {
		logger("Deleting orphaned data from thread table");
		if ($db->q("SELECT `iid` FROM `thread` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `thread`.`iid`)", true)) {
			logger("found thread orphans: ".$db->num_rows());
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `thread` WHERE `iid` = %d", intval($orphan["iid"]));
			}
		}
		$db->qclose();
	}

	if (($stage == 2) OR ($stage == 0)) {
		logger("Deleting orphaned data from notify table");
		if ($db->q("SELECT `iid` FROM `notify` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `notify`.`iid`)", true)) {
			logger("found notify orphans: ".$db->num_rows());
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `notify` WHERE `iid` = %d", intval($orphan["iid"]));
			}
		}
		$db->qclose();
	}


	if (($stage == 3) OR ($stage == 0)) {
		logger("Deleting orphaned data from sign table");
		if ($db->q("SELECT `iid` FROM `sign` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `sign`.`iid`)", true)) {
			logger("found sign orphans: ".$db->num_rows());
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `sign` WHERE `iid` = %d", intval($orphan["iid"]));
			}
		}
		$db->qclose();
	}


	if (($stage == 4) OR ($stage == 0)) {
		logger("Deleting orphaned data from term table");
		if ($db->q("SELECT `oid` FROM `term` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `term`.`oid`)", true)) {
			logger("found term orphans: ".$db->num_rows());
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `term` WHERE `oid` = %d", intval($orphan["oid"]));
			}
		}
		$db->qclose();
	}

	/// @todo Based on the following query we should remove some more data
	// SELECT `id`, `received`, `created`, `guid` FROM `item` WHERE `uid` = 0 AND NOT EXISTS (SELECT `guid` FROM `item` AS `i` WHERE `item`.`guid` = `i`.`guid` AND `i`.`uid` != 0) LIMIT 1;

	logger("Done deleting orphaned data from tables");
}

if (array_search(__file__,get_included_files())===0){
  dbclean_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
?>
