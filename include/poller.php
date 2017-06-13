<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Util\Lock;

if (!file_exists("boot.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/") {
		$directory = $_SERVER["PWD"] . "/" . $directory;
	}

	$directory = realpath($directory . "/..");

	chdir($directory);
}

require_once "boot.php";

function poller_run($argv, $argc){
	global $a, $db;

	$a = new App(dirname(__DIR__));

	@include ".htconfig.php";
	require_once "include/dba.php";
	$db = new dba($db_host, $db_user, $db_pass, $db_data);
	unset($db_host, $db_user, $db_pass, $db_data);

	Config::load();

	// Quit when in maintenance
	if (Config::get('system', 'maintenance', true)) {
		return;
	}

	$a->set_baseurl(Config::get('system', 'url'));

	load_hooks();

	// At first check the maximum load. We shouldn't continue with a high load
	if ($a->maxload_reached()) {
		logger('Pre check: maximum load reached, quitting.', LOGGER_DEBUG);
		return;
	}

	// We now start the process. This is done after the load check since this could increase the load.
	$a->start_process();

	// Kill stale processes every 5 minutes
	$last_cleanup = Config::get('system', 'poller_last_cleaned', 0);
	if (time() > ($last_cleanup + 300)) {
		Config::set('system', 'poller_last_cleaned', time());
		poller_kill_stale_workers();
	}

	// Count active workers and compare them with a maximum value that depends on the load
	if (poller_too_much_workers()) {
		logger('Pre check: Active worker limit reached, quitting.', LOGGER_DEBUG);
		return;
	}

	// Do we have too few memory?
	if ($a->min_memory_reached()) {
		logger('Pre check: Memory limit reached, quitting.', LOGGER_DEBUG);
		return;
	}

	// Possibly there are too much database connections
	if (poller_max_connections_reached()) {
		logger('Pre check: maximum connections reached, quitting.', LOGGER_DEBUG);
		return;
	}

	// Possibly there are too much database processes that block the system
	if ($a->max_processes_reached()) {
		return;
	}

	if (!Lock::set('poller_worker')) {
		logger('Pre check: maximum processes reached, quitting.', LOGGER_DEBUG);
		return;
	}

	// Now we start additional cron processes if we should do so
	if (($argc <= 1) || ($argv[1] != "no_cron")) {
		poller_run_cron();
	}

	$starttime = time();

	// We fetch the next queue entry that is about to be executed
	while ($r = poller_worker_process()) {
		foreach ($r AS $entry) {
			// Assure that the priority is an integer value
			$entry['priority'] = (int)$entry['priority'];

			// The work will be done
			if (!poller_execute($entry)) {
				logger('Process execution failed, quitting.', LOGGER_DEBUG);
				return;
			}

			// To avoid the quitting of multiple pollers only one poller at a time will execute the check
			if (Lock::set('poller_worker', 0)) {
				// Count active workers and compare them with a maximum value that depends on the load
				if (poller_too_much_workers()) {
					logger('Active worker limit reached, quitting.', LOGGER_DEBUG);
					return;
				}

				// Check free memory
				if ($a->min_memory_reached()) {
					logger('Memory limit reached, quitting.', LOGGER_DEBUG);
					return;
				}
				Lock::remove('poller_worker');
			}
		}

		// Quit the poller once every hour
		if (time() > ($starttime + 3600)) {
			logger('Process lifetime reached, quitting.', LOGGER_DEBUG);
			return;
		}
	}
	logger("Couldn't select a workerqueue entry, quitting.", LOGGER_DEBUG);
}

/**
 * @brief Returns the number of non executed entries in the worker queue
 *
 * @return integer Number of non executed entries in the worker queue
 */
function poller_total_entries() {
	$s = q("SELECT COUNT(*) AS `total` FROM `workerqueue` WHERE `executed` <= '%s'", dbesc(NULL_DATE));
	return $s[0]["total"];
}

/**
 * @brief Returns the highest priority in the worker queue that isn't executed
 *
 * @return integer Number of active poller processes
 */
function poller_highest_priority() {
	$s = q("SELECT `priority` FROM `workerqueue` WHERE `executed` <= '%s' ORDER BY `priority` LIMIT 1", dbesc(NULL_DATE));
	return $s[0]["priority"];
}

/**
 * @brief Returns if a process with the given priority is running
 *
 * @param integer $priority The priority that should be checked
 *
 * @return integer Is there a process running with that priority?
 */
function poller_process_with_priority_active($priority) {
	$s = q("SELECT `id` FROM `workerqueue` WHERE `priority` <= %d AND `executed` > '%s' LIMIT 1",
			intval($priority), dbesc(NULL_DATE));
	return dbm::is_result($s);
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

	// Quit when in maintenance
	if (Config::get('system', 'maintenance', true)) {
		logger("Maintenance mode - quit process ".$mypid, LOGGER_DEBUG);
		return false;
	}

	// Constantly check the number of parallel database processes
	if ($a->max_processes_reached()) {
		logger("Max processes reached for process ".$mypid, LOGGER_DEBUG);
		return false;
	}

	// Constantly check the number of available database connections to let the frontend be accessible at any time
	if (poller_max_connections_reached()) {
		logger("Max connection reached for process ".$mypid, LOGGER_DEBUG);
		return false;
	}

	$argv = json_decode($queue["parameter"]);

	// Check for existance and validity of the include file
	$include = $argv[0];

	if (!validate_include($include)) {
		logger("Include file ".$argv[0]." is not valid!");
		dba::delete('workerqueue', array('id' => $queue["id"]));
		return true;
	}

	require_once($include);

	$funcname = str_replace(".php", "", basename($argv[0]))."_run";

	if (function_exists($funcname)) {
		poller_exec_function($queue, $funcname, $argv);
		dba::delete('workerqueue', array('id' => $queue["id"]));
	} else {
		logger("Function ".$funcname." does not exist");
	}

	return true;
}

/**
 * @brief Execute a function from the queue
 *
 * @param array $queue Workerqueue entry
 * @param string $funcname name of the function
 * @param array $argv Array of values to be passed to the function
 */
function poller_exec_function($queue, $funcname, $argv) {

	$a = get_app();

	$mypid = getmypid();

	$argc = count($argv);

	logger("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." ".$queue["parameter"]);

	$stamp = (float)microtime(true);

	// We use the callstack here to analyze the performance of executed worker entries.
	// For this reason the variables have to be initialized.
	if (Config::get("system", "profiler")) {
		$a->performance["start"] = microtime(true);
		$a->performance["database"] = 0;
		$a->performance["database_write"] = 0;
		$a->performance["network"] = 0;
		$a->performance["file"] = 0;
		$a->performance["rendering"] = 0;
		$a->performance["parser"] = 0;
		$a->performance["marktime"] = 0;
		$a->performance["markstart"] = microtime(true);
		$a->callstack = array();
	}

	// For better logging create a new process id for every worker call
	// But preserve the old one for the worker
	$old_process_id = $a->process_id;
	$a->process_id = uniqid("wrk", true);
	$a->queue = $queue;

	$funcname($argv, $argc);

	$a->process_id = $old_process_id;
	unset($a->queue);

	$duration = number_format(microtime(true) - $stamp, 3);

	if ($duration > 3600) {
		logger("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 1 hour (".round($duration/60, 3).")", LOGGER_DEBUG);
	} elseif ($duration > 600) {
		logger("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 10 minutes (".round($duration/60, 3).")", LOGGER_DEBUG);
	} elseif ($duration > 300) {
		logger("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 5 minutes (".round($duration/60, 3).")", LOGGER_DEBUG);
	} elseif ($duration > 120) {
		logger("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 2 minutes (".round($duration/60, 3).")", LOGGER_DEBUG);
	}

	logger("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." - done in ".$duration." seconds.");

	// Write down the performance values into the log
	if (Config::get("system", "profiler")) {
		$duration = microtime(true)-$a->performance["start"];

		if (Config::get("rendertime", "callstack")) {
			if (isset($a->callstack["database"])) {
				$o = "\nDatabase Read:\n";
				foreach ($a->callstack["database"] as $func => $time) {
					$time = round($time, 3);
					if ($time > 0) {
						$o .= $func.": ".$time."\n";
					}
				}
			}
			if (isset($a->callstack["database_write"])) {
				$o .= "\nDatabase Write:\n";
				foreach ($a->callstack["database_write"] as $func => $time) {
					$time = round($time, 3);
					if ($time > 0) {
						$o .= $func.": ".$time."\n";
					}
				}
			}
			if (isset($a->callstack["network"])) {
				$o .= "\nNetwork:\n";
				foreach ($a->callstack["network"] as $func => $time) {
					$time = round($time, 3);
					if ($time > 0) {
						$o .= $func.": ".$time."\n";
					}
				}
			}
		} else {
			$o = '';
		}

		logger("ID ".$queue["id"].": ".$funcname.": ".sprintf("DB: %s/%s, Net: %s, I/O: %s, Other: %s, Total: %s".$o,
			number_format($a->performance["database"] - $a->performance["database_write"], 2),
			number_format($a->performance["database_write"], 2),
			number_format($a->performance["network"], 2),
			number_format($a->performance["file"], 2),
			number_format($duration - ($a->performance["database"] + $a->performance["network"] + $a->performance["file"]), 2),
			number_format($duration, 2)),
			LOGGER_DEBUG);
	}

	$cooldown = Config::get("system", "worker_cooldown", 0);

	if ($cooldown > 0) {
		logger("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." - in cooldown for ".$cooldown." seconds");
		sleep($cooldown);
	}
}

/**
 * @brief Checks if the number of database connections has reached a critical limit.
 *
 * @return bool Are more than 3/4 of the maximum connections used?
 */
function poller_max_connections_reached() {

	// Fetch the max value from the config. This is needed when the system cannot detect the correct value by itself.
	$max = Config::get("system", "max_connections");

	// Fetch the percentage level where the poller will get active
	$maxlevel = Config::get("system", "max_connections_level", 75);

	if ($max == 0) {
		// the maximum number of possible user connections can be a system variable
		$r = q("SHOW VARIABLES WHERE `variable_name` = 'max_user_connections'");
		if (dbm::is_result($r)) {
			$max = $r[0]["Value"];
		}
		// Or it can be granted. This overrides the system variable
		$r = q("SHOW GRANTS");
		if (dbm::is_result($r)) {
			foreach ($r as $grants) {
				$grant = array_pop($grants);
				if (stristr($grant, "GRANT USAGE ON")) {
					if (preg_match("/WITH MAX_USER_CONNECTIONS (\d*)/", $grant, $match)) {
						$max = $match[1];
					}
				}
			}
		}
	}

	// If $max is set we will use the processlist to determine the current number of connections
	// The processlist only shows entries of the current user
	if ($max != 0) {
		$r = q("SHOW PROCESSLIST");
		if (!dbm::is_result($r)) {
			return false;
		}
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
	if (!dbm::is_result($r)) {
		return false;
	}
	$max = intval($r[0]["Value"]);
	if ($max == 0) {
		return false;
	}
	$r = q("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
	if (!dbm::is_result($r)) {
		return false;
	}
	$used = intval($r[0]["Value"]);
	if ($used == 0) {
		return false;
	}
	logger("Connection usage (system values): ".$used."/".$max, LOGGER_DEBUG);

	$level = $used / $max * 100;

	if ($level < $maxlevel) {
		return false;
	}
	logger("Maximum level (".$level."%) of system connections reached: ".$used."/".$max);
	return true;
}

/**
 * @brief fix the queue entry if the worker process died
 *
 */
function poller_kill_stale_workers() {
	$r = q("SELECT `pid`, `executed`, `priority`, `parameter` FROM `workerqueue` WHERE `executed` > '%s'", dbesc(NULL_DATE));

	if (!dbm::is_result($r)) {
		// No processing here needed
		return;
	}

	foreach ($r as $pid) {
		if (!posix_kill($pid["pid"], 0)) {
			dba::update('workerqueue', array('executed' => NULL_DATE, 'pid' => 0),
					array('pid' => $pid["pid"]));
		} else {
			// Kill long running processes

			// Check if the priority is in a valid range
			if (!in_array($pid["priority"], array(PRIORITY_CRITICAL, PRIORITY_HIGH, PRIORITY_MEDIUM, PRIORITY_LOW, PRIORITY_NEGLIGIBLE))) {
				$pid["priority"] = PRIORITY_MEDIUM;
			}

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
				dba::update('workerqueue',
						array('executed' => NULL_DATE, 'created' => datetime_convert(), 'priority' => PRIORITY_NEGLIGIBLE, 'pid' => 0),
						array('pid' => $pid["pid"]));
			} else {
				logger("Worker process ".$pid["pid"]." (".implode(" ", $argv).") now runs for ".round($duration)." of ".$max_duration." allowed minutes. That's okay.", LOGGER_DEBUG);
			}
		}
	}
}

/**
 * @brief Checks if the number of active workers exceeds the given limits
 *
 * @param integer $entries The number of not executed entries in the worker queue
 * @param integer $top_priority The highest not executed priority in the worker queue
 * @param boolean $high_running Is a process with priority "$top_priority" running?
 *
 * @return bool Are there too much workers running?
 */
function poller_too_much_workers() {
	$queues = Config::get("system", "worker_queues", 4);

	$maxqueues = $queues;

	$active = poller_active_workers();

	// Decrease the number of workers at higher load
	$load = current_load();
	if ($load) {
		$maxsysload = intval(Config::get("system", "maxloadavg", 50));

		$maxworkers = $queues;

		// Some magical mathemathics to reduce the workers
		$exponent = 3;
		$slope = $maxworkers / pow($maxsysload, $exponent);
		$queues = ceil($slope * pow(max(0, $maxsysload - $load), $exponent));

		if (Config::get('system', 'worker_debug')) {
			// Create a list of queue entries grouped by their priority
			$listitem = array();

			// Adding all processes with no workerqueue entry
			$processes = dba::p("SELECT COUNT(*) AS `running` FROM `process` WHERE NOT EXISTS (SELECT id FROM `workerqueue` WHERE `workerqueue`.`pid` = `process`.`pid`)");
			if ($process = dba::fetch($processes)) {
				$listitem[0] = "0:".$process["running"];
			}
			dba::close($processes);

			// Now adding all processes with workerqueue entries
			$entries = dba::p("SELECT COUNT(*) AS `entries`, `priority` FROM `workerqueue` GROUP BY `priority`");
			while ($entry = dba::fetch($entries)) {
				$processes = dba::p("SELECT COUNT(*) AS `running` FROM `process` INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` WHERE `priority` = ?", $entry["priority"]);
				if ($process = dba::fetch($processes)) {
					$listitem[$entry["priority"]] = $entry["priority"].":".$process["running"]."/".$entry["entries"];
				}
				dba::close($processes);
			}
			dba::close($entries);
			$processlist = ' ('.implode(', ', $listitem).')';
		}

		$entries = poller_total_entries();

		if (Config::get("system", "worker_fastlane", false) && ($queues > 0) && ($entries > 0) && ($active >= $queues)) {
			$top_priority = poller_highest_priority();
			$high_running = poller_process_with_priority_active($top_priority);

			if (!$high_running && ($top_priority > PRIORITY_UNDEFINED) && ($top_priority < PRIORITY_NEGLIGIBLE)) {
				logger("There are jobs with priority ".$top_priority." waiting but none is executed. Open a fastlane.", LOGGER_DEBUG);
				$queues = $active + 1;
			}
		}

		logger("Load: ".$load."/".$maxsysload." - processes: ".$active."/".$entries.$processlist." - maximum: ".$queues."/".$maxqueues, LOGGER_DEBUG);

		// Are there fewer workers running as possible? Then fork a new one.
		if (!Config::get("system", "worker_dont_fork") && ($queues > ($active + 1)) && ($entries > 1)) {
			logger("Active workers: ".$active."/".$queues." Fork a new worker.", LOGGER_DEBUG);
			$args = array("include/poller.php", "no_cron");
			$a = get_app();
			$a->proc_run($args);
		}
	}

	return $active >= $queues;
}

/**
 * @brief Returns the number of active poller processes
 *
 * @return integer Number of active poller processes
 */
function poller_active_workers() {
	$workers = q("SELECT COUNT(*) AS `processes` FROM `process` WHERE `command` = 'poller.php'");

	return $workers[0]["processes"];
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
	if (!dbm::is_result($r)) {
		return false;
	}
	$priorities = array();
	foreach ($r as $line) {
		$priorities[] = $line["priority"];
	}
	// Should not happen
	if (count($priorities) == 0) {
		return false;
	}
	$highest_priority = min($priorities);

	// The highest process is already the slowest one?
	// Then we quit
	if ($highest_priority == PRIORITY_NEGLIGIBLE) {
		return false;
	}
	$high = 0;
	foreach ($priorities as $priority) {
		if ($priority == $highest_priority) {
			++$high;
		}
	}
	logger("Highest priority: " . $highest_priority . " Total processes: " . count($priorities) . " Count high priority processes: " . $high, LOGGER_DEBUG);
	$passing_slow = (($high / count($priorities)) > (2 / 3));

	if ($passing_slow) {
		logger("Passing slower processes than priority " . $highest_priority, LOGGER_DEBUG);
	}
	return $passing_slow;
}

/**
 * @brief Find and claim the next worker process for us
 *
 * @return boolean Have we found something?
 */
function find_worker_processes() {
	// Check if we should pass some low priority process
	$highest_priority = 0;
	$found = false;

	if (poller_passing_slow($highest_priority)) {
		// Are there waiting processes with a higher priority than the currently highest?
		$result = dba::p("UPDATE `workerqueue` SET `executed` = ?, `pid` = ?
					WHERE `executed` <= ? AND `priority` < ?
					ORDER BY `priority`, `created` LIMIT 1",
				datetime_convert(), getmypid(), NULL_DATE, $highest_priority);
		if (dbm::is_result($result)) {
			$found = (dba::num_rows($result) > 0);
		}
		dba::close($result);

		if (!$found) {
			// Give slower processes some processing time
			$result = dba::p("UPDATE `workerqueue` SET `executed` = ?, `pid` = ?
						WHERE `executed` <= ? AND `priority` > ?
						ORDER BY `priority`, `created` LIMIT 1",
					datetime_convert(), getmypid(), NULL_DATE, $highest_priority);
			if (dbm::is_result($result)) {
				$found = (dba::num_rows($result) > 0);
			}
			dba::close($result);
		}
	}

	// If there is no result (or we shouldn't pass lower processes) we check without priority limit
	if (!$found) {
		$result = dba::p("UPDATE `workerqueue` SET `executed` = ?, `pid` = ? WHERE `executed` <= ? ORDER BY `priority`, `created` LIMIT 1",
				datetime_convert(), getmypid(), NULL_DATE);
		if (dbm::is_result($result)) {
			$found = (dba::num_rows($result) > 0);
		}
		dba::close($result);
	}
	return $found;
}

/**
 * @brief Returns the next worker process
 *
 * @return string SQL statement
 */
function poller_worker_process() {

	$stamp = (float)microtime(true);

	$timeout = 10;
	do {
		$found = find_worker_processes();
	} while (!$found && (poller_total_entries() > 0) && (--$timeout > 0));

	logger('Duration: '.number_format(microtime(true) - $stamp, 3), LOGGER_DEBUG);

	if ($found) {
		$r = q("SELECT * FROM `workerqueue` WHERE `pid` = %d", intval(getmypid()));
	}
	return $r;
}

/**
 * @brief Removes a workerqueue entry from the current process
 */
function poller_unclaim_process() {
	$mypid = getmypid();

	dba::update('workerqueue', array('executed' => NULL_DATE, 'pid' => 0), array('pid' => $mypid));
}

/**
 * @brief Call the front end worker
 */
function call_worker() {
	if (!Config::get("system", "frontend_worker")) {
		return;
	}

	$url = App::get_baseurl()."/worker";
	fetch_url($url, false, $redirects, 1);
}

/**
 * @brief Call the front end worker if there aren't any active
 */
function call_worker_if_idle() {
	if (!Config::get("system", "frontend_worker")) {
		return;
	}

	// Do we have "proc_open"? Then we can fork the poller
	if (function_exists("proc_open")) {
		// When was the last time that we called the worker?
		// Less than one minute? Then we quit
		if ((time() - Config::get("system", "worker_started")) < 60) {
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

		$args = array("include/poller.php", "no_cron");
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

	poller_unclaim_process();

	get_app()->end_process();

	Lock::remove('poller_worker');

	killme();
}
