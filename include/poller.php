<?php
if (!file_exists("boot.php") AND (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/")
		$directory = $_SERVER["PWD"]."/".$directory;

	$directory = realpath($directory."/..");

	chdir($directory);
}

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

require_once("boot.php");

function poller_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}

	if(is_null($db)) {
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	// Quit when in maintenance
	if (get_config('system', 'maintenance', true))
		return;

	$a->start_process();

	$mypid = getmypid();

	if ($a->max_processes_reached())
		return;

	if (poller_max_connections_reached())
		return;

	if (App::maxload_reached())
		return;

	// Checking the number of workers
	if (poller_too_much_workers()) {
		poller_kill_stale_workers();
		return;
	}

	if(($argc <= 1) OR ($argv[1] != "no_cron")) {
		// Run the cron job that calls all other jobs
		proc_run(PRIORITY_MEDIUM, "include/cron.php");

		// Run the cronhooks job separately from cron for being able to use a different timing
		proc_run(PRIORITY_MEDIUM, "include/cronhooks.php");

		// Cleaning dead processes
		poller_kill_stale_workers();
	} else
		// Sleep four seconds before checking for running processes again to avoid having too many workers
		sleep(4);

	// Checking number of workers
	if (poller_too_much_workers())
		return;

	$cooldown = Config::get("system", "worker_cooldown", 0);

	$starttime = time();

	while ($r = q("SELECT * FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00' ORDER BY `priority`, `created` LIMIT 1")) {

		// Quit when in maintenance
		if (get_config('system', 'maintenance', true))
			return;

		// Constantly check the number of parallel database processes
		if ($a->max_processes_reached())
			return;

		// Constantly check the number of available database connections to let the frontend be accessible at any time
		if (poller_max_connections_reached())
			return;

		// Count active workers and compare them with a maximum value that depends on the load
		if (poller_too_much_workers())
			return;

		q("UPDATE `workerqueue` SET `executed` = '%s', `pid` = %d WHERE `id` = %d AND `executed` = '0000-00-00 00:00:00'",
			dbesc(datetime_convert()),
			intval($mypid),
			intval($r[0]["id"]));

		// Assure that there are no tasks executed twice
		$id = q("SELECT `pid`, `executed` FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
		if (!$id) {
			logger("Queue item ".$r[0]["id"]." vanished - skip this execution", LOGGER_DEBUG);
			continue;
		} elseif ((strtotime($id[0]["executed"]) <= 0) OR ($id[0]["pid"] == 0)) {
			logger("Entry for queue item ".$r[0]["id"]." wasn't stored - we better stop here", LOGGER_DEBUG);
			return;
		} elseif ($id[0]["pid"] != $mypid) {
			logger("Queue item ".$r[0]["id"]." is to be executed by process ".$id[0]["pid"]." and not by me (".$mypid.") - skip this execution", LOGGER_DEBUG);
			continue;
		}

		$argv = json_decode($r[0]["parameter"]);

		$argc = count($argv);

		// Check for existance and validity of the include file
		$include = $argv[0];

		if (!validate_include($include)) {
			logger("Include file ".$argv[0]." is not valid!");
			q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
			continue;
		}

		require_once($include);

		$funcname = str_replace(".php", "", basename($argv[0]))."_run";

		if (function_exists($funcname)) {
			logger("Process ".$mypid." - Prio ".$r[0]["priority"]." - ID ".$r[0]["id"].": ".$funcname." ".$r[0]["parameter"]);
			$funcname($argv, $argc);

			if ($cooldown > 0) {
				logger("Process ".$mypid." - Prio ".$r[0]["priority"]." - ID ".$r[0]["id"].": ".$funcname." - in cooldown for ".$cooldown." seconds");
				sleep($cooldown);
			}

			logger("Process ".$mypid." - Prio ".$r[0]["priority"]." - ID ".$r[0]["id"].": ".$funcname." - done");

			q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
		} else
			logger("Function ".$funcname." does not exist");

		// Quit the poller once every hour
		if (time() > ($starttime + 3600))
			return;
	}

}

/**
 * @brief Checks if the number of database connections has reached a critical limit.
 *
 * @return bool Are more than 3/4 of the maximum connections used?
 */
function poller_max_connections_reached() {

	// Fetch the max value from the config. This is needed when the system cannot detect the correct value by itself.
	$max = get_config("system", "max_connections");

	// Fetch the percentage level where the poller will get active
	$maxlevel = get_config("system", "max_connections_level");
	if ($maxlevel == 0)
		$maxlevel = 75;

	if ($max == 0) {
		// the maximum number of possible user connections can be a system variable
		$r = q("SHOW VARIABLES WHERE `variable_name` = 'max_user_connections'");
		if ($r)
			$max = $r[0]["Value"];

		// Or it can be granted. This overrides the system variable
		$r = q("SHOW GRANTS");
		if ($r)
			foreach ($r AS $grants) {
				$grant = array_pop($grants);
				if (stristr($grant, "GRANT USAGE ON"))
					if (preg_match("/WITH MAX_USER_CONNECTIONS (\d*)/", $grant, $match))
						$max = $match[1];
			}
	}

	// If $max is set we will use the processlist to determine the current number of connections
	// The processlist only shows entries of the current user
	if ($max != 0) {
		$r = q("SHOW PROCESSLIST");
		if (!$r)
			return false;

		$used = count($r);

		logger("Connection usage (user values): ".$used."/".$max, LOGGER_DEBUG);

		$level = ($used / $max) * 100;

		if ($level >= $maxlevel) {
			logger("Maximum level (".$maxlevel."%) of user connections reached: ".$used."/".$max);
			return true;
		}
	}

	// We will now check for the system values.
	// This limit could be reached although the user limits are fine.
	$r = q("SHOW VARIABLES WHERE `variable_name` = 'max_connections'");
	if (!$r)
		return false;

	$max = intval($r[0]["Value"]);
	if ($max == 0)
		return false;

	$r = q("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
	if (!$r)
		return false;

	$used = intval($r[0]["Value"]);
	if ($used == 0)
		return false;

	logger("Connection usage (system values): ".$used."/".$max, LOGGER_DEBUG);

	$level = $used / $max * 100;

	if ($level < $maxlevel)
		return false;

	logger("Maximum level (".$level."%) of system connections reached: ".$used."/".$max);
	return true;
}

/**
 * @brief fix the queue entry if the worker process died
 *
 */
function poller_kill_stale_workers() {
	$r = q("SELECT `pid`, `executed`, `priority`, `parameter` FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");

	if (!dbm::is_result($r)) {
		// No processing here needed
		return;
	}

	foreach($r AS $pid)
		if (!posix_kill($pid["pid"], 0))
			q("UPDATE `workerqueue` SET `executed` = '0000-00-00 00:00:00', `pid` = 0 WHERE `pid` = %d",
				intval($pid["pid"]));
		else {
			// Kill long running processes

			// Check if the priority is in a valid range
			if (!in_array($pid["priority"], array(PRIORITY_CRITICAL, PRIORITY_HIGH, PRIORITY_MEDIUM, PRIORITY_LOW, PRIORITY_NEGLIGIBLE)))
				$pid["priority"] = PRIORITY_MEDIUM;

			// Define the maximum durations
			$max_duration_defaults = array(PRIORITY_CRITICAL => 360, PRIORITY_HIGH => 10, PRIORITY_MEDIUM => 60, PRIORITY_LOW => 180, PRIORITY_NEGLIGIBLE => 360);
			$max_duration = $max_duration_defaults[$pid["priority"]];

			$argv = json_decode($pid["parameter"]);
			$argv[0] = basename($argv[0]);

			// How long is the process already running?
			$duration = (time() - strtotime($pid["executed"])) / 60;
			if ($duration > $max_duration) {
				logger("Worker process ".$pid["pid"]." (".implode(" ", $argv).") took more than ".$max_duration." minutes. It will be killed now.");
				posix_kill($pid["pid"], SIGTERM);

				// We killed the stale process.
				// To avoid a blocking situation we reschedule the process at the beginning of the queue.
				// Additionally we are lowering the priority.
				q("UPDATE `workerqueue` SET `executed` = '0000-00-00 00:00:00', `created` = '%s',
							`priority` = %d, `pid` = 0 WHERE `pid` = %d",
					dbesc(datetime_convert()),
					intval(PRIORITY_NEGLIGIBLE),
					intval($pid["pid"]));
			} else
				logger("Worker process ".$pid["pid"]." (".implode(" ", $argv).") now runs for ".round($duration)." of ".$max_duration." allowed minutes. That's okay.", LOGGER_DEBUG);
		}
}

function poller_too_much_workers() {


	$queues = get_config("system", "worker_queues");

	if ($queues == 0)
		$queues = 4;

	$maxqueues = $queues;

	$active = poller_active_workers();

	// Decrease the number of workers at higher load
	$load = current_load();
	if($load) {
		$maxsysload = intval(get_config('system','maxloadavg'));
		if($maxsysload < 1)
			$maxsysload = 50;

		$maxworkers = $queues;

		// Some magical mathemathics to reduce the workers
		$exponent = 3;
		$slope = $maxworkers / pow($maxsysload, $exponent);
		$queues = ceil($slope * pow(max(0, $maxsysload - $load), $exponent));

		$s = q("SELECT COUNT(*) AS `total` FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00'");
		$entries = $s[0]["total"];

		if (Config::get("system", "worker_fastlane", false) AND ($queues > 0) AND ($entries > 0) AND ($active >= $queues)) {
			$s = q("SELECT `priority` FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00' ORDER BY `priority` LIMIT 1");
			$top_priority = $s[0]["priority"];

			$s = q("SELECT `id` FROM `workerqueue` WHERE `priority` <= %d AND `executed` != '0000-00-00 00:00:00' LIMIT 1",
				intval($top_priority));
			$high_running = dbm::is_result($s);

			if (!$high_running AND ($top_priority > PRIORITY_UNDEFINED) AND ($top_priority < PRIORITY_NEGLIGIBLE)) {
				logger("There are jobs with priority ".$top_priority." waiting but none is executed. Open a fastlane.", LOGGER_DEBUG);
				$queues = $active + 1;
			}
		}

		logger("Current load: ".$load." - maximum: ".$maxsysload." - current queues: ".$active."/".$entries." - maximum: ".$queues."/".$maxqueues, LOGGER_DEBUG);

		// Are there fewer workers running as possible? Then fork a new one.
		if (!get_config("system", "worker_dont_fork") AND ($queues > ($active + 1)) AND ($entries > 1)) {
			logger("Active workers: ".$active."/".$queues." Fork a new worker.", LOGGER_DEBUG);
			$args = array("php", "include/poller.php", "no_cron");
			$a = get_app();
			$a->proc_run($args);
		}
	}

	return($active >= $queues);
}

function poller_active_workers() {
	$workers = q("SELECT COUNT(*) AS `processes` FROM `process` WHERE `command` = 'poller.php'");

	return($workers[0]["processes"]);
}

if (array_search(__file__,get_included_files())===0){
	poller_run($_SERVER["argv"],$_SERVER["argc"]);

	get_app()->end_process();

	killme();
}
?>
