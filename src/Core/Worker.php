<?php
/**
 * @file src/Core/Worker.php
 */
namespace Friendica\Core;

use Friendica\Core;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Process;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;

/**
 * @file src/Core/Worker.php
 *
 * @brief Contains the class for the worker background job processing
 */

/**
 * @brief Worker methods
 */
class Worker
{
	const STATE_STARTUP    = 1; // Worker is in startup. This takes most time.
	const STATE_LONG_LOOP  = 2; // Worker is processing the whole - long - loop.
	const STATE_REFETCH    = 3; // Worker had refetched jobs in the execution loop.
	const STATE_SHORT_LOOP = 4; // Worker is processing preassigned jobs, thus saving much time.

	const FAST_COMMANDS = ['APDelivery', 'Delivery', 'CreateShadowEntry'];


	private static $up_start;
	private static $db_duration = 0;
	private static $db_duration_count = 0;
	private static $db_duration_write = 0;
	private static $db_duration_stat = 0;
	private static $lock_duration = 0;
	private static $last_update;
	private static $state;

	/**
	 * @brief Processes the tasks that are in the workerqueue table
	 *
	 * @param boolean $run_cron Should the cron processes be executed?
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function processQueue($run_cron = true)
	{
		$a = \get_app();

		// Ensure that all "strtotime" operations do run timezone independent
		date_default_timezone_set('UTC');

		self::$up_start = microtime(true);

		// At first check the maximum load. We shouldn't continue with a high load
		if ($a->isMaxLoadReached()) {
			Logger::log('Pre check: maximum load reached, quitting.', Logger::DEBUG);
			return;
		}

		// We now start the process. This is done after the load check since this could increase the load.
		self::startProcess();

		// Kill stale processes every 5 minutes
		$last_cleanup = Config::get('system', 'worker_last_cleaned', 0);
		if (time() > ($last_cleanup + 300)) {
			Config::set('system', 'worker_last_cleaned', time());
			self::killStaleWorkers();
		}

		// Count active workers and compare them with a maximum value that depends on the load
		if (self::tooMuchWorkers()) {
			Logger::log('Pre check: Active worker limit reached, quitting.', Logger::DEBUG);
			return;
		}

		// Do we have too few memory?
		if ($a->isMinMemoryReached()) {
			Logger::log('Pre check: Memory limit reached, quitting.', Logger::DEBUG);
			return;
		}

		// Possibly there are too much database connections
		if (self::maxConnectionsReached()) {
			Logger::log('Pre check: maximum connections reached, quitting.', Logger::DEBUG);
			return;
		}

		// Possibly there are too much database processes that block the system
		if ($a->isMaxProcessesReached()) {
			Logger::log('Pre check: maximum processes reached, quitting.', Logger::DEBUG);
			return;
		}

		// Now we start additional cron processes if we should do so
		if ($run_cron) {
			self::runCron();
		}

		$starttime = time();
		self::$state = self::STATE_STARTUP;

		// We fetch the next queue entry that is about to be executed
		while ($r = self::workerProcess()) {
			$refetched = false;
			foreach ($r as $entry) {
				// Assure that the priority is an integer value
				$entry['priority'] = (int)$entry['priority'];

				// The work will be done
				if (!self::execute($entry)) {
					Logger::log('Process execution failed, quitting.', Logger::DEBUG);
					return;
				}

				// Trying to fetch new processes - but only once when successful
				if (!$refetched && Lock::acquire('worker_process', 0)) {
					self::findWorkerProcesses();
					Lock::release('worker_process');
					self::$state = self::STATE_REFETCH;
					$refetched = true;
				} else {
					self::$state = self::STATE_SHORT_LOOP;
				}
			}

			// To avoid the quitting of multiple workers only one worker at a time will execute the check
			if (!self::getWaitingJobForPID()) {
				self::$state = self::STATE_LONG_LOOP;

				if (Lock::acquire('worker', 0)) {
				// Count active workers and compare them with a maximum value that depends on the load
					if (self::tooMuchWorkers()) {
						Logger::log('Active worker limit reached, quitting.', Logger::DEBUG);
						Lock::release('worker');
						return;
					}

					// Check free memory
					if ($a->isMinMemoryReached()) {
						Logger::log('Memory limit reached, quitting.', Logger::DEBUG);
						Lock::release('worker');
						return;
					}
					Lock::release('worker');
				}
			}

			// Quit the worker once every cron interval
			if (time() > ($starttime + (Config::get('system', 'cron_interval') * 60))) {
				Logger::info('Process lifetime reached, respawning.');
				self::spawnWorker();
				return;
			}
		}

		// Cleaning up. Possibly not needed, but it doesn't harm anything.
		if (Config::get('system', 'worker_daemon_mode', false)) {
			self::IPCSetJobState(false);
		}
		Logger::log("Couldn't select a workerqueue entry, quitting process " . getmypid() . ".", Logger::DEBUG);
	}

	/**
	 * @brief Check if non executed tasks do exist in the worker queue
	 *
	 * @return boolean Returns "true" if tasks are existing
	 * @throws \Exception
	 */
	private static function entriesExists()
	{
		$stamp = (float)microtime(true);
		$exists = DBA::exists('workerqueue', ["NOT `done` AND `pid` = 0 AND `next_try` < ?", DateTimeFormat::utcNow()]);
		self::$db_duration += (microtime(true) - $stamp);
		return $exists;
	}

	/**
	 * @brief Returns the number of deferred entries in the worker queue
	 *
	 * @return integer Number of deferred entries in the worker queue
	 * @throws \Exception
	 */
	private static function deferredEntries()
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('workerqueue', ["NOT `done` AND `pid` = 0 AND `retrial` > ?", 0]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * @brief Returns the number of non executed entries in the worker queue
	 *
	 * @return integer Number of non executed entries in the worker queue
	 * @throws \Exception
	 */
	private static function totalEntries()
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('workerqueue', ['done' => false, 'pid' => 0]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * @brief Returns the highest priority in the worker queue that isn't executed
	 *
	 * @return integer Number of active worker processes
	 * @throws \Exception
	 */
	private static function highestPriority()
	{
		$stamp = (float)microtime(true);
		$condition = ["`pid` = 0 AND NOT `done` AND `next_try` < ?", DateTimeFormat::utcNow()];
		$workerqueue = DBA::selectFirst('workerqueue', ['priority'], $condition, ['order' => ['priority']]);
		self::$db_duration += (microtime(true) - $stamp);
		if (DBA::isResult($workerqueue)) {
			return $workerqueue["priority"];
		} else {
			return 0;
		}
	}

	/**
	 * @brief Returns if a process with the given priority is running
	 *
	 * @param integer $priority The priority that should be checked
	 *
	 * @return integer Is there a process running with that priority?
	 * @throws \Exception
	 */
	private static function processWithPriorityActive($priority)
	{
		$condition = ["`priority` <= ? AND `pid` != 0 AND NOT `done`", $priority];
		return DBA::exists('workerqueue', $condition);
	}

	/**
	 * @brief Execute a worker entry
	 *
	 * @param array $queue Workerqueue entry
	 *
	 * @return boolean "true" if further processing should be stopped
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function execute($queue)
	{
		$a = \get_app();

		$mypid = getmypid();

		// Quit when in maintenance
		if (Config::get('system', 'maintenance', false, true)) {
			Logger::log("Maintenance mode - quit process ".$mypid, Logger::DEBUG);
			return false;
		}

		// Constantly check the number of parallel database processes
		if ($a->isMaxProcessesReached()) {
			Logger::log("Max processes reached for process ".$mypid, Logger::DEBUG);
			return false;
		}

		// Constantly check the number of available database connections to let the frontend be accessible at any time
		if (self::maxConnectionsReached()) {
			Logger::log("Max connection reached for process ".$mypid, Logger::DEBUG);
			return false;
		}

		$argv = json_decode($queue["parameter"], true);

		// Check for existance and validity of the include file
		$include = $argv[0];

		if (method_exists(sprintf('Friendica\Worker\%s', $include), 'execute')) {
			// We constantly update the "executed" date every minute to avoid being killed too soon
			if (!isset(self::$last_update)) {
				self::$last_update = strtotime($queue["executed"]);
			}

			$age = (time() - self::$last_update) / 60;
			self::$last_update = time();

			if ($age > 1) {
				$stamp = (float)microtime(true);
				DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow()], ['pid' => $mypid, 'done' => false]);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			}

			array_shift($argv);

			self::execFunction($queue, $include, $argv, true);

			$stamp = (float)microtime(true);
			$condition = ["`id` = ? AND `next_try` < ?", $queue['id'], DateTimeFormat::utcNow()];
			if (DBA::update('workerqueue', ['done' => true], $condition)) {
				Config::set('system', 'last_worker_execution', DateTimeFormat::utcNow());
			}
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);

			return true;
		}

		// The script could be provided as full path or only with the function name
		if ($include == basename($include)) {
			$include = "include/".$include.".php";
		}

		if (!validate_include($include)) {
			Logger::log("Include file ".$argv[0]." is not valid!");
			$stamp = (float)microtime(true);
			DBA::delete('workerqueue', ['id' => $queue["id"]]);
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
			return true;
		}

		require_once $include;

		$funcname = str_replace(".php", "", basename($argv[0]))."_run";

		if (function_exists($funcname)) {
			// We constantly update the "executed" date every minute to avoid being killed too soon
			if (!isset(self::$last_update)) {
				self::$last_update = strtotime($queue["executed"]);
			}

			$age = (time() - self::$last_update) / 60;
			self::$last_update = time();

			if ($age > 1) {
				$stamp = (float)microtime(true);
				DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow()], ['pid' => $mypid, 'done' => false]);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			}

			self::execFunction($queue, $funcname, $argv, false);

			$stamp = (float)microtime(true);
			if (DBA::update('workerqueue', ['done' => true], ['id' => $queue["id"]])) {
				Config::set('system', 'last_worker_execution', DateTimeFormat::utcNow());
			}
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		} else {
			Logger::log("Function ".$funcname." does not exist");
			$stamp = (float)microtime(true);
			DBA::delete('workerqueue', ['id' => $queue["id"]]);
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		}

		return true;
	}

	/**
	 * @brief Execute a function from the queue
	 *
	 * @param array   $queue       Workerqueue entry
	 * @param string  $funcname    name of the function
	 * @param array   $argv        Array of values to be passed to the function
	 * @param boolean $method_call boolean
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function execFunction($queue, $funcname, $argv, $method_call)
	{
		$a = \get_app();

		$argc = count($argv);

		Logger::enableWorker($funcname);

		Logger::info("Process start.", ['priority' => $queue["priority"], 'id' => $queue["id"]]);

		$stamp = (float)microtime(true);

		// We use the callstack here to analyze the performance of executed worker entries.
		// For this reason the variables have to be initialized.
		$a->getProfiler()->reset();

		$a->queue = $queue;

		$up_duration = microtime(true) - self::$up_start;

		// Reset global data to avoid interferences
		unset($_SESSION);

		// Set the workerLogger as new default logger
		if ($method_call) {
			call_user_func_array(sprintf('Friendica\Worker\%s::execute', $funcname), $argv);
		} else {
			$funcname($argv, $argc);
		}

		Logger::disableWorker();

		unset($a->queue);

		$duration = (microtime(true) - $stamp);

		/* With these values we can analyze how effective the worker is.
		 * The database and rest time should be low since this is the unproductive time.
		 * The execution time is the productive time.
		 * By changing parameters like the maximum number of workers we can check the effectivness.
		*/
		$dbtotal = round(self::$db_duration, 2);
		$dbread  = round(self::$db_duration - (self::$db_duration_count + self::$db_duration_write + self::$db_duration_stat), 2);
		$dbcount = round(self::$db_duration_count, 2);
		$dbstat  = round(self::$db_duration_stat, 2);
		$dbwrite = round(self::$db_duration_write, 2);
		$dblock  = round(self::$lock_duration, 2);
		$rest    = round(max(0, $up_duration - (self::$db_duration + self::$lock_duration)), 2);
		$exec    = round($duration, 2);

		Logger::info('Performance:', ['state' => self::$state, 'count' => $dbcount, 'stat' => $dbstat, 'write' => $dbwrite, 'lock' => $dblock, 'total' => $dbtotal, 'rest' => $rest, 'exec' => $exec]);

		self::$up_start = microtime(true);
		self::$db_duration = 0;
		self::$db_duration_count = 0;
		self::$db_duration_stat = 0;
		self::$db_duration_write = 0;
		self::$lock_duration = 0;

		if ($duration > 3600) {
			Logger::info('Longer than 1 hour.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		} elseif ($duration > 600) {
			Logger::info('Longer than 10 minutes.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		} elseif ($duration > 300) {
			Logger::info('Longer than 5 minutes.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		} elseif ($duration > 120) {
			Logger::info('Longer than 2 minutes.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		}

		Logger::info('Process done.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration, 3)]);

		$a->getProfiler()->saveLog($a->getLogger(), "ID " . $queue["id"] . ": " . $funcname);

		$cooldown = Config::get("system", "worker_cooldown", 0);

		if ($cooldown > 0) {
			Logger::info('Cooldown.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'cooldown' => $cooldown]);
			sleep($cooldown);
		}
	}

	/**
	 * @brief Checks if the number of database connections has reached a critical limit.
	 *
	 * @return bool Are more than 3/4 of the maximum connections used?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function maxConnectionsReached()
	{
		// Fetch the max value from the config. This is needed when the system cannot detect the correct value by itself.
		$max = Config::get("system", "max_connections");

		// Fetch the percentage level where the worker will get active
		$maxlevel = Config::get("system", "max_connections_level", 75);

		if ($max == 0) {
			// the maximum number of possible user connections can be a system variable
			$r = DBA::fetchFirst("SHOW VARIABLES WHERE `variable_name` = 'max_user_connections'");
			if (DBA::isResult($r)) {
				$max = $r["Value"];
			}
			// Or it can be granted. This overrides the system variable
			$stamp = (float)microtime(true);
			$r = DBA::p('SHOW GRANTS');
			self::$db_duration += (microtime(true) - $stamp);
			while ($grants = DBA::fetch($r)) {
				$grant = array_pop($grants);
				if (stristr($grant, "GRANT USAGE ON")) {
					if (preg_match("/WITH MAX_USER_CONNECTIONS (\d*)/", $grant, $match)) {
						$max = $match[1];
					}
				}
			}
			DBA::close($r);
		}

		// If $max is set we will use the processlist to determine the current number of connections
		// The processlist only shows entries of the current user
		if ($max != 0) {
			$stamp = (float)microtime(true);
			$r = DBA::p('SHOW PROCESSLIST');
			self::$db_duration += (microtime(true) - $stamp);
			$used = DBA::numRows($r);
			DBA::close($r);

			Logger::log("Connection usage (user values): ".$used."/".$max, Logger::DEBUG);

			$level = ($used / $max) * 100;

			if ($level >= $maxlevel) {
				Logger::log("Maximum level (".$maxlevel."%) of user connections reached: ".$used."/".$max);
				return true;
			}
		}

		// We will now check for the system values.
		// This limit could be reached although the user limits are fine.
		$r = DBA::fetchFirst("SHOW VARIABLES WHERE `variable_name` = 'max_connections'");
		if (!DBA::isResult($r)) {
			return false;
		}
		$max = intval($r["Value"]);
		if ($max == 0) {
			return false;
		}
		$r = DBA::fetchFirst("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
		if (!DBA::isResult($r)) {
			return false;
		}
		$used = intval($r["Value"]);
		if ($used == 0) {
			return false;
		}
		Logger::log("Connection usage (system values): ".$used."/".$max, Logger::DEBUG);

		$level = $used / $max * 100;

		if ($level < $maxlevel) {
			return false;
		}
		Logger::log("Maximum level (".$level."%) of system connections reached: ".$used."/".$max);
		return true;
	}

	/**
	 * @brief fix the queue entry if the worker process died
	 * @return void
	 * @throws \Exception
	 */
	private static function killStaleWorkers()
	{
		$stamp = (float)microtime(true);
		$entries = DBA::select(
			'workerqueue',
			['id', 'pid', 'executed', 'priority', 'parameter'],
			['NOT `done` AND `pid` != 0'],
			['order' => ['priority', 'created']]
		);
		self::$db_duration += (microtime(true) - $stamp);

		while ($entry = DBA::fetch($entries)) {
			if (!posix_kill($entry["pid"], 0)) {
				$stamp = (float)microtime(true);
				DBA::update(
					'workerqueue',
					['executed' => DBA::NULL_DATETIME, 'pid' => 0],
					['id' => $entry["id"]]
				);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			} else {
				// Kill long running processes
				// Check if the priority is in a valid range
				if (!in_array($entry["priority"], [PRIORITY_CRITICAL, PRIORITY_HIGH, PRIORITY_MEDIUM, PRIORITY_LOW, PRIORITY_NEGLIGIBLE])) {
					$entry["priority"] = PRIORITY_MEDIUM;
				}

				// Define the maximum durations
				$max_duration_defaults = [PRIORITY_CRITICAL => 720, PRIORITY_HIGH => 10, PRIORITY_MEDIUM => 60, PRIORITY_LOW => 180, PRIORITY_NEGLIGIBLE => 720];
				$max_duration = $max_duration_defaults[$entry["priority"]];

				$argv = json_decode($entry["parameter"], true);
				$argv[0] = basename($argv[0]);

				// How long is the process already running?
				$duration = (time() - strtotime($entry["executed"])) / 60;
				if ($duration > $max_duration) {
					Logger::log("Worker process ".$entry["pid"]." (".substr(json_encode($argv), 0, 50).") took more than ".$max_duration." minutes. It will be killed now.");
					posix_kill($entry["pid"], SIGTERM);

					// We killed the stale process.
					// To avoid a blocking situation we reschedule the process at the beginning of the queue.
					// Additionally we are lowering the priority. (But not PRIORITY_CRITICAL)
					$new_priority = $entry["priority"];
					if ($entry["priority"] == PRIORITY_HIGH) {
						$new_priority = PRIORITY_MEDIUM;
					} elseif ($entry["priority"] == PRIORITY_MEDIUM) {
						$new_priority = PRIORITY_LOW;
					} elseif ($entry["priority"] != PRIORITY_CRITICAL) {
						$new_priority = PRIORITY_NEGLIGIBLE;
					}
					$stamp = (float)microtime(true);
					DBA::update(
						'workerqueue',
						['executed' => DBA::NULL_DATETIME, 'created' => DateTimeFormat::utcNow(), 'priority' => $new_priority, 'pid' => 0],
						['id' => $entry["id"]]
					);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_write += (microtime(true) - $stamp);
				} else {
					Logger::log("Worker process ".$entry["pid"]." (".substr(json_encode($argv), 0, 50).") now runs for ".round($duration)." of ".$max_duration." allowed minutes. That's okay.", Logger::DEBUG);
				}
			}
		}
	}

	/**
	 * @brief Checks if the number of active workers exceeds the given limits
	 *
	 * @return bool Are there too much workers running?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function tooMuchWorkers()
	{
		$queues = Config::get("system", "worker_queues", 10);

		$maxqueues = $queues;

		$active = self::activeWorkers();

		// Decrease the number of workers at higher load
		$load = System::currentLoad();
		if ($load) {
			$maxsysload = intval(Config::get("system", "maxloadavg", 20));

			/* Default exponent 3 causes queues to rapidly decrease as load increases.
			 * If you have 20 max queues at idle, then you get only 5 queues at 37.1% of $maxsysload.
			 * For some environments, this rapid decrease is not needed.
			 * With exponent 1, you could have 20 max queues at idle and 13 at 37% of $maxsysload.
			 */
			$exponent = intval(Config::get('system', 'worker_load_exponent', 3));
			$slope = pow(max(0, $maxsysload - $load) / $maxsysload, $exponent);
			$queues = intval(ceil($slope * $maxqueues));

			$processlist = '';

			if (Config::get('system', 'worker_jpm')) {
				$intervals = explode(',', Config::get('system', 'worker_jpm_range'));
				$jobs_per_minute = [];
				foreach ($intervals as $interval) {
					if ($interval == 0) {
						continue;
					} else {
						$interval = (int)$interval;
					}

					$stamp = (float)microtime(true);
					$jobs = DBA::p("SELECT COUNT(*) AS `jobs` FROM `workerqueue` WHERE `done` AND `executed` > UTC_TIMESTAMP() - INTERVAL ? MINUTE", $interval);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_stat += (microtime(true) - $stamp);
					if ($job = DBA::fetch($jobs)) {
						$jobs_per_minute[$interval] = number_format($job['jobs'] / $interval, 0);
					}
					DBA::close($jobs);
				}
				$processlist = ' - jpm: '.implode('/', $jobs_per_minute);
			}

			// Create a list of queue entries grouped by their priority
			$listitem = [0 => ''];

			$idle_workers = $active;

			$deferred = self::deferredEntries();

			if (Config::get('system', 'worker_debug')) {
				$waiting_processes = 0;
				// Now adding all processes with workerqueue entries
				$stamp = (float)microtime(true);
				$jobs = DBA::p("SELECT COUNT(*) AS `entries`, `priority` FROM `workerqueue` WHERE NOT `done` GROUP BY `priority`");
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_stat += (microtime(true) - $stamp);
				while ($entry = DBA::fetch($jobs)) {
					$stamp = (float)microtime(true);
					$processes = DBA::p("SELECT COUNT(*) AS `running` FROM `process` INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` WHERE NOT `done` AND `priority` = ?", $entry["priority"]);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_stat += (microtime(true) - $stamp);
					if ($process = DBA::fetch($processes)) {
						$idle_workers -= $process["running"];
						$waiting_processes += $entry["entries"];
						$listitem[$entry["priority"]] = $entry["priority"].":".$process["running"]."/".$entry["entries"];
					}
					DBA::close($processes);
				}
				DBA::close($jobs);
			} else {
				$waiting_processes =  self::totalEntries();
				$stamp = (float)microtime(true);
				$jobs = DBA::p("SELECT COUNT(*) AS `running`, `priority` FROM `process` INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` AND NOT `done` GROUP BY `priority` ORDER BY `priority`");
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_stat += (microtime(true) - $stamp);

				while ($entry = DBA::fetch($jobs)) {
					$idle_workers -= $entry["running"];
					$listitem[$entry["priority"]] = $entry["priority"].":".$entry["running"];
				}
				DBA::close($jobs);
			}

			$waiting_processes -= $deferred;

			$listitem[0] = "0:" . max(0, $idle_workers);

			$processlist .= ' ('.implode(', ', $listitem).')';

			if (Config::get("system", "worker_fastlane", false) && ($queues > 0) && ($active >= $queues) && self::entriesExists()) {
				$top_priority = self::highestPriority();
				$high_running = self::processWithPriorityActive($top_priority);

				if (!$high_running && ($top_priority > PRIORITY_UNDEFINED) && ($top_priority < PRIORITY_NEGLIGIBLE)) {
					Logger::log("There are jobs with priority ".$top_priority." waiting but none is executed. Open a fastlane.", Logger::DEBUG);
					$queues = $active + 1;
				}
			}

			Logger::log("Load: " . $load ."/" . $maxsysload . " - processes: " . $deferred . "/" . $active . "/" . $waiting_processes . $processlist . " - maximum: " . $queues . "/" . $maxqueues, Logger::DEBUG);

			// Are there fewer workers running as possible? Then fork a new one.
			if (!Config::get("system", "worker_dont_fork", false) && ($queues > ($active + 1)) && self::entriesExists()) {
				Logger::log("Active workers: ".$active."/".$queues." Fork a new worker.", Logger::DEBUG);
				if (Config::get('system', 'worker_daemon_mode', false)) {
					self::IPCSetJobState(true);
				} else {
					self::spawnWorker();
				}
			}
		}

		// if there are too much worker, we don't spawn a new one.
		if (Config::get('system', 'worker_daemon_mode', false) && ($active > $queues)) {
			self::IPCSetJobState(false);
		}

		return $active > $queues;
	}

	/**
	 * @brief Returns the number of active worker processes
	 *
	 * @return integer Number of active worker processes
	 * @throws \Exception
	 */
	private static function activeWorkers()
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('process', ['command' => 'Worker.php']);
		self::$db_duration += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * @brief Returns waiting jobs for the current process id
	 *
	 * @return array waiting workerqueue jobs
	 * @throws \Exception
	 */
	private static function getWaitingJobForPID()
	{
		$stamp = (float)microtime(true);
		$r = DBA::select('workerqueue', [], ['pid' => getmypid(), 'done' => false]);
		self::$db_duration += (microtime(true) - $stamp);
		if (DBA::isResult($r)) {
			return DBA::toArray($r);
		}
		DBA::close($r);

		return false;
	}

	/**
	 * @brief Returns the next jobs that should be executed
	 *
	 * @return array array with next jobs
	 * @throws \Exception
	 */
	private static function nextProcess()
	{
		$priority = self::nextPriority();
		if (empty($priority)) {
			Logger::info('No tasks found');
			return [];
		}

		$limit = Config::get('system', 'worker_fetch_limit', 1);

		$ids = [];
		$stamp = (float)microtime(true);
		$condition = ["`priority` = ? AND `pid` = 0 AND NOT `done` AND `next_try` < ?", $priority, DateTimeFormat::utcNow()];
		$tasks = DBA::select('workerqueue', ['id', 'parameter'], $condition, ['limit' => $limit, 'order' => ['created']]);
		self::$db_duration += (microtime(true) - $stamp);
		while ($task = DBA::fetch($tasks)) {
			$ids[] = $task['id'];
			// Only continue that loop while we are storing commands that can be processed quickly
			$command = json_decode($task['parameter'])[0];
			if (!in_array($command, self::FAST_COMMANDS)) {
				break;
			}
		}
		DBA::close($tasks);

		Logger::info('Found:', ['priority' => $priority, 'id' => $ids]);
		return $ids;
	}

	/**
	 * @brief Returns the priority of the next workerqueue job
	 *
	 * @return string priority
	 * @throws \Exception
	 */
	private static function nextPriority()
	{
		$waiting = [];
		$priorities = [PRIORITY_CRITICAL, PRIORITY_HIGH, PRIORITY_MEDIUM, PRIORITY_LOW, PRIORITY_NEGLIGIBLE];
		foreach ($priorities as $priority) {
			$stamp = (float)microtime(true);
			if (DBA::exists('workerqueue', ["`priority` = ? AND `pid` = 0 AND NOT `done` AND `next_try` < ?", $priority, DateTimeFormat::utcNow()])) {
				$waiting[$priority] = true;
			}
			self::$db_duration += (microtime(true) - $stamp);
		}

		if (!empty($waiting[PRIORITY_CRITICAL])) {
			return PRIORITY_CRITICAL;
		}

		$running = [];
		$running_total = 0;
		$stamp = (float)microtime(true);
		$processes = DBA::p("SELECT COUNT(DISTINCT(`process`.`pid`)) AS `running`, `priority` FROM `process`
			INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid`
			WHERE NOT `done` GROUP BY `priority`");
		self::$db_duration += (microtime(true) - $stamp);
		while ($process = DBA::fetch($processes)) {
			$running[$process['priority']] = $process['running'];
			$running_total += $process['running'];
		}
		DBA::close($processes);

		foreach ($priorities as $priority) {
			if (!empty($waiting[$priority]) && empty($running[$priority])) {
				Logger::info('No running worker found with priority {priority} - assigning it.', ['priority' => $priority]);
				return $priority;
			}
		}

		$active = max(self::activeWorkers(), $running_total);
		$priorities = max(count($waiting), count($running));
		$exponent = 2;

		$total = 0;
		for ($i = 1; $i <= $priorities; ++$i) {
			$total += pow($i, $exponent);
		}

		$limit = [];
		for ($i = 1; $i <= $priorities; ++$i) {
			$limit[$priorities - $i] = max(1, round($active * (pow($i, $exponent) / $total)));
		}

		$i = 0;
		foreach ($running as $priority => $workers) {
			if ($workers < $limit[$i++]) {
				Logger::info('Priority {priority} has got {workers} workers out of a limit of {limit}', ['priority' => $priority, 'workers' => $workers, 'limit' => $limit[$i - 1]]);
				return $priority;
			}
		}

		if (!empty($waiting)) {
			$priority = array_keys($waiting)[0];
			Logger::info('No underassigned priority found, now taking the highest priority.', ['priority' => $priority]);
			return $priority;
		}

		return false;
	}

	/**
	 * @brief Find and claim the next worker process for us
	 *
	 * @return boolean Have we found something?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function findWorkerProcesses()
	{
		$mypid = getmypid();

		$ids = self::nextProcess();

		// If there is no result we check without priority limit
		if (empty($ids)) {
			$limit = Config::get('system', 'worker_fetch_limit', 1);

			$stamp = (float)microtime(true);
			$condition = ["`pid` = 0 AND NOT `done` AND `next_try` < ?", DateTimeFormat::utcNow()];
			$tasks = DBA::select('workerqueue', ['id', 'parameter'], $condition, ['limit' => $limit, 'order' => ['priority', 'created']]);
			self::$db_duration += (microtime(true) - $stamp);

			while ($task = DBA::fetch($tasks)) {
				$ids[] = $task['id'];
				// Only continue that loop while we are storing commands that can be processed quickly
				$command = json_decode($task['parameter'])[0];
				if (!in_array($command, self::FAST_COMMANDS)) {
					break;
				}
			}
			DBA::close($tasks);
		}

		if (!empty($ids)) {
			$stamp = (float)microtime(true);
			$condition = ['id' => $ids, 'done' => false, 'pid' => 0];
			DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow(), 'pid' => $mypid], $condition);
			self::$db_duration += (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		}

		return !empty($ids);
	}

	/**
	 * @brief Returns the next worker process
	 *
	 * @return string SQL statement
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function workerProcess()
	{
		// There can already be jobs for us in the queue.
		$waiting = self::getWaitingJobForPID();
		if (!empty($waiting)) {
			return $waiting;
		}

		$stamp = (float)microtime(true);
		if (!Lock::acquire('worker_process')) {
			return false;
		}
		self::$lock_duration += (microtime(true) - $stamp);

		$found = self::findWorkerProcesses();

		Lock::release('worker_process');

		if ($found) {
			$stamp = (float)microtime(true);
			$r = DBA::select('workerqueue', [], ['pid' => getmypid(), 'done' => false]);
			self::$db_duration += (microtime(true) - $stamp);
			return DBA::toArray($r);
		}
		return false;
	}

	/**
	 * @brief Removes a workerqueue entry from the current process
	 * @return void
	 * @throws \Exception
	 */
	public static function unclaimProcess()
	{
		$mypid = getmypid();

		$stamp = (float)microtime(true);
		DBA::update('workerqueue', ['executed' => DBA::NULL_DATETIME, 'pid' => 0], ['pid' => $mypid, 'done' => false]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * @brief Call the front end worker
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function callWorker()
	{
		if (!Config::get("system", "frontend_worker")) {
			return;
		}

		$url = System::baseUrl()."/worker";
		Network::fetchUrl($url, false, 1);
	}

	/**
	 * @brief Call the front end worker if there aren't any active
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function executeIfIdle()
	{
		if (!Config::get("system", "frontend_worker")) {
			return;
		}

		// Do we have "proc_open"? Then we can fork the worker
		if (function_exists("proc_open")) {
			// When was the last time that we called the worker?
			// Less than one minute? Then we quit
			if ((time() - Config::get("system", "worker_started")) < 60) {
				return;
			}

			Config::set("system", "worker_started", time());

			// Do we have enough running workers? Then we quit here.
			if (self::tooMuchWorkers()) {
				// Cleaning dead processes
				self::killStaleWorkers();
				Process::deleteInactive();

				return;
			}

			self::runCron();

			Logger::log('Call worker', Logger::DEBUG);
			self::spawnWorker();
			return;
		}

		// We cannot execute background processes.
		// We now run the processes from the frontend.
		// This won't work with long running processes.
		self::runCron();

		self::clearProcesses();

		$workers = self::activeWorkers();

		if ($workers == 0) {
			self::callWorker();
		}
	}

	/**
	 * @brief Removes long running worker processes
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function clearProcesses()
	{
		$timeout = Config::get("system", "frontend_worker_timeout", 10);

		/// @todo We should clean up the corresponding workerqueue entries as well
		$stamp = (float)microtime(true);
		$condition = ["`created` < ? AND `command` = 'worker.php'",
				DateTimeFormat::utc("now - ".$timeout." minutes")];
		DBA::delete('process', $condition);
		self::$db_duration = (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * @brief Runs the cron processes
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function runCron()
	{
		Logger::log('Add cron entries', Logger::DEBUG);

		// Check for spooled items
		self::add(['priority' => PRIORITY_HIGH, 'force_priority' => true], 'SpoolPost');

		// Run the cron job that calls all other jobs
		self::add(['priority' => PRIORITY_MEDIUM, 'force_priority' => true], 'Cron');

		// Cleaning dead processes
		self::killStaleWorkers();
	}

	/**
	 * @brief Spawns a new worker
	 * @param bool $do_cron
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function spawnWorker($do_cron = false)
	{
		$command = 'bin/worker.php';

		$args = ['no_cron' => !$do_cron];

		$a = get_app();
		$process = new Core\Process($a->getLogger(), $a->getMode(), $a->getConfig(), $a->getBasePath());
		$process->run($command, $args);

		// after spawning we have to remove the flag.
		if (Config::get('system', 'worker_daemon_mode', false)) {
			self::IPCSetJobState(false);
		}
	}

	/**
	 * @brief Adds tasks to the worker queue
	 *
	 * @param (integer|array) priority or parameter array, strings are deprecated and are ignored
	 *
	 * next args are passed as $cmd command line
	 * or: Worker::add(PRIORITY_HIGH, "Notifier", Delivery::DELETION, $drop_id);
	 * or: Worker::add(array('priority' => PRIORITY_HIGH, 'dont_fork' => true), "CreateShadowEntry", $post_id);
	 *
	 * @return boolean "false" if worker queue entry already existed or there had been an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @note $cmd and string args are surrounded with ""
	 *
	 * @hooks 'proc_run'
	 *    array $arr
	 *
	 */
	public static function add($cmd)
	{
		$args = func_get_args();

		if (!count($args)) {
			return false;
		}

		$arr = ['args' => $args, 'run_cmd' => true];

		Hook::callAll("proc_run", $arr);
		if (!$arr['run_cmd'] || !count($args)) {
			return true;
		}

		$priority = PRIORITY_MEDIUM;
		// Don't fork from frontend tasks by default
		$dont_fork = Config::get("system", "worker_dont_fork", false) || !\get_app()->getMode()->isBackend();
		$created = DateTimeFormat::utcNow();
		$force_priority = false;

		$run_parameter = array_shift($args);

		if (is_int($run_parameter)) {
			$priority = $run_parameter;
		} elseif (is_array($run_parameter)) {
			if (isset($run_parameter['priority'])) {
				$priority = $run_parameter['priority'];
			}
			if (isset($run_parameter['created'])) {
				$created = $run_parameter['created'];
			}
			if (isset($run_parameter['dont_fork'])) {
				$dont_fork = $run_parameter['dont_fork'];
			}
			if (isset($run_parameter['force_priority'])) {
				$force_priority = $run_parameter['force_priority'];
			}
		}

		$parameters = json_encode($args);
		$found = DBA::exists('workerqueue', ['parameter' => $parameters, 'done' => false]);
		$added = false;

		// Quit if there was a database error - a precaution for the update process to 3.5.3
		if (DBA::errorNo() != 0) {
			return false;
		}

		if (!$found) {
			$added = DBA::insert('workerqueue', ['parameter' => $parameters, 'created' => $created, 'priority' => $priority]);
			if (!$added) {
				return false;
			}
		} elseif ($force_priority) {
			DBA::update('workerqueue', ['priority' => $priority], ['parameter' => $parameters, 'done' => false, 'pid' => 0]);
		}

		// Should we quit and wait for the worker to be called as a cronjob?
		if ($dont_fork) {
			return $added;
		}

		// If there is a lock then we don't have to check for too much worker
		if (!Lock::acquire('worker', 0)) {
			return $added;
		}

		// If there are already enough workers running, don't fork another one
		$quit = self::tooMuchWorkers();
		Lock::release('worker');

		if ($quit) {
			return $added;
		}

		// We tell the daemon that a new job entry exists
		if (Config::get('system', 'worker_daemon_mode', false)) {
			// We don't have to set the IPC flag - this is done in "tooMuchWorkers"
			return $added;
		}

		// Now call the worker to execute the jobs that we just added to the queue
		self::spawnWorker();

		return $added;
	}

	/**
	 * Returns the next retrial level for worker jobs.
	 * This function will skip levels when jobs are older.
	 *
	 * @param array $queue Worker queue entry
	 * @param integer $max_level maximum retrial level
	 * @return integer the next retrial level value
	 */
	private static function getNextRetrial($queue, $max_level)
	{
		$created = strtotime($queue['created']);
		$retrial_time = time() - $created;

		$new_retrial = $queue['retrial'] + 1;
		$total = 0;
		for ($retrial = 0; $retrial <= $max_level + 1; ++$retrial) {
			$delay = (($retrial + 3) ** 4) + (rand(1, 30) * ($retrial + 1));
			$total += $delay;
			if (($total < $retrial_time) && ($retrial > $queue['retrial'])) {
				$new_retrial = $retrial;
			}
		}
		Logger::info('New retrial for task', ['id' => $queue['id'], 'created' => $queue['created'], 'old' => $queue['retrial'], 'new' => $new_retrial]);
		return $new_retrial;
	}

	/**
	 * Defers the current worker entry
	 * @return boolean had the entry been deferred?
	 */
	public static function defer()
	{
		if (empty(DI::app()->queue)) {
			return false;
		}

		$queue = DI::app()->queue;

		$retrial = $queue['retrial'];
		$id = $queue['id'];
		$priority = $queue['priority'];

		$max_level = Config::get('system', 'worker_defer_limit');

		$new_retrial = self::getNextRetrial($queue, $max_level);

		if ($new_retrial > $max_level) {
			Logger::info('The task exceeded the maximum retry count', ['id' => $id, 'created' => $queue['created'], 'old_prio' => $queue['priority'], 'old_retrial' => $queue['retrial'], 'max_level' => $max_level, 'retrial' => $new_retrial]);
			return false;
		}

		// Calculate the delay until the next trial
		$delay = (($new_retrial + 2) ** 4) + (rand(1, 30) * ($new_retrial));
		$next = DateTimeFormat::utc('now + ' . $delay . ' seconds');

		if (($priority < PRIORITY_MEDIUM) && ($new_retrial > 3)) {
			$priority = PRIORITY_MEDIUM;
		} elseif (($priority < PRIORITY_LOW) && ($new_retrial > 6)) {
			$priority = PRIORITY_LOW;
		} elseif (($priority < PRIORITY_NEGLIGIBLE) && ($new_retrial > 8)) {
			$priority = PRIORITY_NEGLIGIBLE;
		}

		Logger::info('Deferred task', ['id' => $id, 'retrial' => $new_retrial, 'created' => $queue['created'], 'next_execution' => $next, 'old_prio' => $queue['priority'], 'new_prio' => $priority]);

		$stamp = (float)microtime(true);
		$fields = ['retrial' => $new_retrial, 'next_try' => $next, 'executed' => DBA::NULL_DATETIME, 'pid' => 0, 'priority' => $priority];
		DBA::update('workerqueue', $fields, ['id' => $id]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);

		return true;
	}

	/**
	 * Log active processes into the "process" table
	 *
	 * @brief Log active processes into the "process" table
	 */
	public static function startProcess()
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

		$command = basename($trace[0]['file']);

		Process::deleteInactive();

		Process::insert($command);
	}

	/**
	 * Remove the active process from the "process" table
	 *
	 * @brief Remove the active process from the "process" table
	 * @return bool
	 * @throws \Exception
	 */
	public static function endProcess()
	{
		return Process::deleteByPid();
	}

	/**
	 * Set the flag if some job is waiting
	 *
	 * @brief Set the flag if some job is waiting
	 * @param boolean $jobs Is there a waiting job?
	 * @throws \Exception
	 */
	public static function IPCSetJobState($jobs)
	{
		$stamp = (float)microtime(true);
		DBA::update('worker-ipc', ['jobs' => $jobs], ['key' => 1], true);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * Checks if some worker job waits to be executed
	 *
	 * @brief Checks if some worker job waits to be executed
	 * @return bool
	 * @throws \Exception
	 */
	public static function IPCJobsExists()
	{
		$stamp = (float)microtime(true);
		$row = DBA::selectFirst('worker-ipc', ['jobs'], ['key' => 1]);
		self::$db_duration += (microtime(true) - $stamp);

		// When we don't have a row, no job is running
		if (!DBA::isResult($row)) {
			return false;
		}

		return (bool)$row['jobs'];
	}
}
