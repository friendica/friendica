<?php
/**
 * @file include/dbclean.php
 * @brief The script is called from time to time to clean the database entries and remove orphaned data.
 */

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

require_once("boot.php");

function dbclean_run(&$argv, &$argc) {
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

	if (!Config::get('system', 'dbclean', false)) {
		return;
	}

	if ($argc == 2) {
		$stage = intval($argv[1]);
	} else {
		$stage = 0;
	}

	if (Config::get("system", "worker") AND ($stage == 0)) {
		proc_run(PRIORITY_LOW, 'include/dbclean.php', 1);
		proc_run(PRIORITY_LOW, 'include/dbclean.php', 2);
		proc_run(PRIORITY_LOW, 'include/dbclean.php', 3);
		proc_run(PRIORITY_LOW, 'include/dbclean.php', 4);
		proc_run(PRIORITY_LOW, 'include/dbclean.php', 5);
		proc_run(PRIORITY_LOW, 'include/dbclean.php', 6);
		proc_run(PRIORITY_LOW, 'include/dbclean.php', 7);
	} else {
		remove_orphans($stage);
	}
}

/**
 * @brief Remove orphaned database entries
 */
function remove_orphans($stage = 0) {
	global $db;

	$count = 0;

	// With activated worker we split the deletion in many small tasks
	if (Config::get("system", "worker")) {
		$limit = 1000;
	} else {
		$limit = 10000;
	}

	if (($stage == 1) OR ($stage == 0)) {
		logger("Deleting old global item entries from item table without user copy");
		if ($db->q("SELECT `id` FROM `item` WHERE `uid` = 0
				AND NOT EXISTS (SELECT `guid` FROM `item` AS `i` WHERE `item`.`guid` = `i`.`guid` AND `i`.`uid` != 0)
				AND `received` < UTC_TIMESTAMP() - INTERVAL 90 DAY LIMIT ".intval($limit), true)) {
			$count = $db->num_rows();
			logger("found global item orphans: ".$count);
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `item` WHERE `id` = %d", intval($orphan["id"]));
			}
		}
		$db->qclose();
		logger("Done deleting old global item entries from item table without user copy");
	}

	if (($stage == 2) OR ($stage == 0)) {
		logger("Deleting items without parents");
		if ($db->q("SELECT `id` FROM `item` WHERE NOT EXISTS (SELECT `id` FROM `item` AS `i` WHERE `item`.`parent` = `i`.`id`) LIMIT ".intval($limit), true)) {
			$count = $db->num_rows();
			logger("found item orphans without parents: ".$count);
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `item` WHERE `id` = %d", intval($orphan["id"]));
			}
		}
		$db->qclose();
		logger("Done deleting items without parents");
	}

	if (($stage == 3) OR ($stage == 0)) {
		logger("Deleting orphaned data from thread table");
		if ($db->q("SELECT `iid` FROM `thread` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `thread`.`iid`) LIMIT ".intval($limit), true)) {
			$count = $db->num_rows();
			logger("found thread orphans: ".$count);
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `thread` WHERE `iid` = %d", intval($orphan["iid"]));
			}
		}
		$db->qclose();
		logger("Done deleting orphaned data from thread table");
	}

	if (($stage == 4) OR ($stage == 0)) {
		logger("Deleting orphaned data from notify table");
		if ($db->q("SELECT `iid` FROM `notify` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `notify`.`iid`) LIMIT ".intval($limit), true)) {
			$count = $db->num_rows();
			logger("found notify orphans: ".$count);
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `notify` WHERE `iid` = %d", intval($orphan["iid"]));
			}
		}
		$db->qclose();
		logger("Done deleting orphaned data from notify table");
	}

	if (($stage == 5) OR ($stage == 0)) {
		logger("Deleting orphaned data from notify-threads table");
		if ($db->q("SELECT `id` FROM `notify-threads` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `notify-threads`.`master-parent-item`) LIMIT ".intval($limit), true)) {
			$count = $db->num_rows();
			logger("found notify-threads orphans: ".$count);
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `notify-threads` WHERE `id` = %d", intval($orphan["id"]));
			}
		}
		$db->qclose();
		logger("Done deleting orphaned data from notify-threads table");
	}


	if (($stage == 6) OR ($stage == 0)) {
		logger("Deleting orphaned data from sign table");
		if ($db->q("SELECT `iid` FROM `sign` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `sign`.`iid`) LIMIT ".intval($limit), true)) {
			$count = $db->num_rows();
			logger("found sign orphans: ".$count);
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `sign` WHERE `iid` = %d", intval($orphan["iid"]));
			}
		}
		$db->qclose();
		logger("Done deleting orphaned data from sign table");
	}


	if (($stage == 7) OR ($stage == 0)) {
		logger("Deleting orphaned data from term table");
		if ($db->q("SELECT `oid` FROM `term` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `term`.`oid`) LIMIT ".intval($limit), true)) {
			$count = $db->num_rows();
			logger("found term orphans: ".$count);
			while ($orphan = $db->qfetch()) {
				q("DELETE FROM `term` WHERE `oid` = %d", intval($orphan["oid"]));
			}
		}
		$db->qclose();
		logger("Done deleting orphaned data from term table");
	}

	// Call it again if not all entries were purged
	if (($stage != 0) AND ($count > 0) AND Config::get("system", "worker")) {
		proc_run(PRIORITY_MEDIUM, 'include/dbclean.php');
	}

}

if (array_search(__file__,get_included_files())===0){
  dbclean_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
?>
