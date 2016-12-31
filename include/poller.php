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

function poller_run($argv, $argc){
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

	if (poller_max_connections_reached()) {
		return;
	}

	if ($a->maxload_reached()) {
		return;
	}

	if(($argc <= 1) OR ($argv[1] != "no_cron")) {
		poller_run_cron();
	}

	if ($a->max_processes_reached()) {
		return;
	}

	// Checking the number of workers
	if (poller_too_much_workers()) {
		poller_kill_stale_workers();
		return;
	}

	$starttime = time();

	while ($r = poller_worker_process()) {

		// Count active workers and compare them with a maximum value that depends on the load
		if (poller_too_much_workers()) {
			return;
		}

		if (!poller_execute($r[0])) {
			return;
		}

		// Quit the poller once every hour
		if (time() > ($starttime + 3600))
			return;
	}

}

/**
 * @brief Execute a worker entry
 *
 * @param array $queue Workerqueue entry
 *
 * @return boolean "true" if further processing should be stopped
 */
function poller_execute($queue) {

	$a = get_app();

	$mypid = getmypid();

	$cooldown = Config::get("system", "worker_cooldown", 0);

	// Quit when in maintenance
	if (get_config('system', 'maintenance', true)) {
		return false;
	}

	// Constantly check the number of parallel database processes
	if ($a->max_processes_reached()) {
		return false;
	}

	// Constantly check the number of available database connections to let the frontend be accessible at any time
	if (poller_max_connections_reached()) {
		return false;
	}

	$upd = q("UPDATE `workerqueue` SET `executed` = '%s', `pid` = %d WHERE `id` = %d AND `pid` = 0",
		dbesc(datetime_convert()),
		intval($mypid),
		intval($queue["id"]));

	if (!$upd) {
		logger("Couldn't update queue entry ".$queue["id"]." - skip this execution", LOGGER_DEBUG);
		q("COMMIT");
		return true;
	}

	// Assure that there are no tasks executed twice
	$id = q("SELECT `pid`, `executed` FROM `workerqueue` WHERE `id` = %d", intval($queue["id"]));
	if (!$id) {
		logger("Queue item ".$queue["id"]." vanished - skip this execution", LOGGER_DEBUG);
		q("COMMIT");
		return true;
	} elseif ((strtotime($id[0]["executed"]) <= 0) OR ($id[0]["pid"] == 0)) {
		logger("Entry for queue item ".$queue["id"]." wasn't stored - skip this execution", LOGGER_DEBUG);
		q("COMMIT");
		return true;
	} elseif ($id[0]["pid"] != $mypid) {
		logger("Queue item ".$queue["id"]." is to be executed by process ".$id[0]["pid"]." and not by me (".$mypid.") - skip this execution", LOGGER_DEBUG);
		q("COMMIT");
		return true;
	}
	q("COMMIT");

	$argv = json_decode($queue["parameter"]);

	$argc = count($argv);

	// Check for existance and validity of the include file
	$include = $argv[0];

	if (!validate_include($include)) {
		logger("Include file ".$argv[0]." is not valid!");
		q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($queue["id"]));
		return true;
	}

	require_once($include);

	$funcname = str_replace(".php", "", basename($argv[0]))."_run";

	if (function_exists($funcname)) {
		logger("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." ".$queue["parameter"]);

		// For better logging create a new process id for every worker call
		// But preserve the old one for the worker
		$old_process_id = $a->process_id;
		$a->process_id = uniqid("wrk", true);

		$funcname($argv, $argc);

		$a->process_id = $old_process_id;

		if ($cooldown > 0) {
			logger("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." - in cooldown for ".$cooldown." seconds");
			sleep($cooldown);
		}

		logger("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." - done");

		q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($queue["id"]));
	} else {
		logger("Function ".$funcname." does not exist");
	}

	return true;
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
	$maxlevel = Config::get("system", "max_connections_level", 75);

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

/**
 * @brief Checks if the number of active workers exceeds the given limits
 *
 * @return bool Are there too much workers running?
 */
function poller_too_much_workers() {
	$queues = Config::get("system", "worker_queues", 4);

	$maxqueues = $queues;

	$active = poller_active_workers();

	// Decrease the number of workers at higher load
	$load = current_load();
	if($load) {
		$maxsysload = intval(Config::get("system", "maxloadavg", 50));

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

		// Create a list of queue entries grouped by their priority
		$running = array(PRIORITY_CRITICAL => 0,
				PRIORITY_HIGH => 0,
				PRIORITY_MEDIUM => 0,
				PRIORITY_LOW => 0,
				PRIORITY_NEGLIGIBLE => 0);

		$r = q("SELECT COUNT(*) AS `running`, `priority` FROM `process` INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` GROUP BY `priority`");
		if (dbm::is_result($r))
			foreach ($r AS $process)
				$running[$process["priority"]] = $process["running"];

		$processlist = "";
		$r = q("SELECT COUNT(*) AS `entries`, `priority` FROM `workerqueue` GROUP BY `priority`");
		if (dbm::is_result($r))
			foreach ($r as $entry) {
				if ($processlist != "")
					$processlist .= ", ";
				$processlist .= $entry["priority"].":".$running[$entry["priority"]]."/".$entry["entries"];
			}

		logger("Load: ".$load."/".$maxsysload." - processes: ".$active."/".$entries." (".$processlist.") - maximum: ".$queues."/".$maxqueues, LOGGER_DEBUG);

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

/**
 * @brief Returns the number of active poller processes
 *
 * @return integer Number of active poller processes
 */
function poller_active_workers() {
	$workers = q("SELECT COUNT(*) AS `processes` FROM `process` WHERE `command` = 'poller.php'");

	return($workers[0]["processes"]);
}

/**
 * @brief Check if we should pass some slow processes
 *
 * When the active processes of the highest priority are using more than 2/3
 * of all processes, we let pass slower processes.
 *
 * @param string $highest_priority Returns the currently highest priority
 * @return bool We let pass a slower process than $highest_priority
 */
function poller_passing_slow(&$highest_priority) {

	$highest_priority = 0;

	$r = q("SELECT `priority`
		FROM `process`
		INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid`");

	// No active processes at all? Fine
	if (!dbm::is_result($r))
		return(false);

	$priorities = array();
	foreach ($r AS $line)
		$priorities[] = $line["priority"];

	// Should not happen
	if (count($priorities) == 0)
		return(false);

	$highest_priority = min($priorities);

	// The highest process is already the slowest one?
	// Then we quit
	if ($highest_priority == PRIORITY_NEGLIGIBLE)
		return(false);

	$high = 0;
	foreach ($priorities AS $priority)
		if ($priority == $highest_priority)
			++$high;

	logger("Highest priority: ".$highest_priority." Total processes: ".count($priorities)." Count high priority processes: ".$high, LOGGER_DEBUG);
	$passing_slow = (($high/count($priorities)) > (2/3));

	if ($passing_slow)
		logger("Passing slower processes than priority ".$highest_priority, LOGGER_DEBUG);

	return($passing_slow);
}

/**
 * @brief Returns the next worker process
 *
 * @return string SQL statement
 */
function poller_worker_process() {

	q("START TRANSACTION;");

	// Check if we should pass some low priority process
	$highest_priority = 0;

	if (poller_passing_slow($highest_priority)) {
		// Are there waiting processes with a higher priority than the currently highest?
		$r = q("SELECT * FROM `workerqueue`
				WHERE `executed` = '0000-00-00 00:00:00' AND `priority` < %d
				ORDER BY `priority`, `created` LIMIT 1", dbesc($highest_priority));
		if (dbm::is_result($r))
			return $r;

		// Give slower processes some processing time
		$r = q("SELECT * FROM `workerqueue`
				WHERE `executed` = '0000-00-00 00:00:00' AND `priority` > %d
				ORDER BY `priority`, `created` LIMIT 1", dbesc($highest_priority));
	}

	// If there is no result (or we shouldn't pass lower processes) we check without priority limit
	if (($highest_priority == 0) OR !dbm::is_result($r))
		$r = q("SELECT * FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00' ORDER BY `priority`, `created` LIMIT 1");

	return $r;
}

/**
 * @brief Call the front end worker
 */
function call_worker() {
	if (!Config::get("system", "frontend_worker") OR !Config::get("system", "worker")) {
		return;
	}

	$url = App::get_baseurl()."/worker";
	fetch_url($url, false, $redirects, 1);
}

/**
 * @brief Call the front end worker if there aren't any active
 */
function call_worker_if_idle() {
	if (!Config::get("system", "frontend_worker") OR !Config::get("system", "worker")) {
		return;
	}

	// Do we have "proc_open"? Then we can fork the poller
	if (function_exists("proc_open")) {
		// When was the last time that we called the worker?
		// Less than one minute? Then we quit
		if ((time() - get_config("system", "worker_started")) < 60) {
			return;
		}

		set_config("system", "worker_started", time());

		// Do we have enough running workers? Then we quit here.
		if (poller_too_much_workers()) {
			// Cleaning dead processes
			poller_kill_stale_workers();
			get_app()->remove_inactive_processes();

			return;
		}

		poller_run_cron();

		logger('Call poller', LOGGER_DEBUG);

		$args = array("php", "include/poller.php", "no_cron");
		$a = get_app();
		$a->proc_run($args);
		return;
	}

	// We cannot execute background processes.
	// We now run the processes from the frontend.
	// This won't work with long running processes.
	poller_run_cron();

	clear_worker_processes();

	$workers = q("SELECT COUNT(*) AS `processes` FROM `process` WHERE `command` = 'worker.php'");

	if ($workers[0]["processes"] == 0) {
		call_worker();
	}
}

/**
 * @brief Removes long running worker processes
 */
function clear_worker_processes() {
	$timeout = Config::get("system", "frontend_worker_timeout", 10);

	/// @todo We should clean up the corresponding workerqueue entries as well
	q("DELETE FROM `process` WHERE `created` < '%s' AND `command` = 'worker.php'",
		dbesc(datetime_convert('UTC','UTC',"now - ".$timeout." minutes")));
}

/**
 * @brief Runs the cron processes
 */
function poller_run_cron() {
	logger('Add cron entries', LOGGER_DEBUG);

	// Check for spooled items
	proc_run(PRIORITY_HIGH, "include/spool_post.php");

	// Run the cron job that calls all other jobs
	proc_run(PRIORITY_MEDIUM, "include/cron.php");

	// Run the cronhooks job separately from cron for being able to use a different timing
	proc_run(PRIORITY_MEDIUM, "include/cronhooks.php");

	// Cleaning dead processes
	poller_kill_stale_workers();
}

if (array_search(__file__,get_included_files())===0){
	poller_run($_SERVER["argv"],$_SERVER["argc"]);

	get_app()->end_process();

	killme();
}
?>
