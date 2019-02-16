<?php
/**
 * @file src/Core/Worker.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Model\Process;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Logger\WorkerLogger;
use Friendica\Util\Network;
use Friendica\Worker\WorkerFactory;

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
	private static $up_start;
	private static $db_duration = 0;
	private static $db_duration_count = 0;
	private static $db_duration_write = 0;
	private static $db_duration_stat = 0;
	private static $lock_duration = 0;
	private static $last_update;

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

		$entries = 0;
		$deferred = 0;

		// We fetch the next queue entry that is about to be executed
		while ($r = self::workerProcess($passing_slow, $entries, $deferred)) {
			// When we are processing jobs with a lower priority, we don't refetch new jobs
			// Otherwise fast jobs could wait behind slow ones and could be blocked.
			$refetched = $passing_slow;

			foreach ($r as $entry) {
				// Assure that the priority is an integer value
				$entry['priority'] = (int)$entry['priority'];

				// The work will be done
				if (!self::execute($entry)) {
					Logger::log('Process execution failed, quitting.', Logger::DEBUG);
					return;
				}

				// If possible we will fetch new jobs for this worker
				if (!$refetched) {
					$entries = self::totalEntries();
					$deferred = self::deferredEntries();
					if (Lock::acquire('worker_process', 0)) {
						$refetched = self::findWorkerProcesses($passing_slow, $entries, $deferred);
						Lock::release('worker_process');
					}
				}
			}

			// To avoid the quitting of multiple workers only one worker at a time will execute the check
			if (Lock::acquire('worker', 0)) {
				// Count active workers and compare them with a maximum value that depends on the load
				if (self::tooMuchWorkers($entries, $deferred)) {
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

			// Quit the worker once every 5 minutes
			if (time() > ($starttime + 300)) {
				Logger::log('Process lifetime reached, quitting.', Logger::DEBUG);
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
		$count = DBA::count('workerqueue', ["NOT `done` AND `pid` = 0 AND `next_try` > ?", DateTimeFormat::utcNow()]);
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

		$mypid = getmypid();

		$workerLogger = new WorkerLogger($a->getLogger());

		$argc = count($argv);

		Logger::log("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." ".$queue["parameter"]." - Process PID: " . $workerLogger->getWorkerId());

		$stamp = (float)microtime(true);

		// We use the callstack here to analyze the performance of executed worker entries.
		// For this reason the variables have to be initialized.
		if (Config::get("system", "profiler")) {
			$a->performance["start"] = microtime(true);
			$a->performance["database"] = 0;
			$a->performance["database_write"] = 0;
			$a->performance["cache"] = 0;
			$a->performance["cache_write"] = 0;
			$a->performance["network"] = 0;
			$a->performance["file"] = 0;
			$a->performance["rendering"] = 0;
			$a->performance["parser"] = 0;
			$a->performance["marktime"] = 0;
			$a->performance["markstart"] = microtime(true);
			$a->callstack = [];
		}

		$up_duration = microtime(true) - self::$up_start;

		// Reset global data to avoid interferences
		unset($_SESSION);

		if ($method_call) {
			$worker = WorkerFactory::create($funcname, $a, $workerLogger);
			/**
			 * This call uses the array as a parameter because of this benchmark
			 * https://github.com/fab2s/call_user_func/
			 */
			$worker->execute($argv);
		} else {
			$funcname($argv, $argc);
		}

		unset($a->queue);

		$duration = (microtime(true) - $stamp);

		/* With these values we can analyze how effective the worker is.
		 * The database and rest time should be low since this is the unproductive time.
		 * The execution time is the productive time.
		 * By changing parameters like the maximum number of workers we can check the effectivness.
		*/
		Logger::log(
			'DB: '.number_format(self::$db_duration - (self::$db_duration_count + self::$db_duration_write + self::$db_duration_stat), 4).
			' - DB-Count: '.number_format(self::$db_duration_count, 4).
			' - DB-Stat: '.number_format(self::$db_duration_stat, 4).
			' - DB-Write: '.number_format(self::$db_duration_write, 4).
			' - Lock: '.number_format(self::$lock_duration, 4).
			' - Rest: '.number_format(max(0, $up_duration - (self::$db_duration + self::$lock_duration)), 4).
			' - Execution: '.number_format($duration, 4),
			Logger::DEBUG
		);

		self::$up_start = microtime(true);
		self::$db_duration = 0;
		self::$db_duration_count = 0;
		self::$db_duration_stat = 0;
		self::$db_duration_write = 0;
		self::$lock_duration = 0;

		if ($duration > 3600) {
			Logger::log("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 1 hour (".round($duration/60, 3).")", Logger::DEBUG);
		} elseif ($duration > 600) {
			Logger::log("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 10 minutes (".round($duration/60, 3).")", Logger::DEBUG);
		} elseif ($duration > 300) {
			Logger::log("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 5 minutes (".round($duration/60, 3).")", Logger::DEBUG);
		} elseif ($duration > 120) {
			Logger::log("Prio ".$queue["priority"].": ".$queue["parameter"]." - longer than 2 minutes (".round($duration/60, 3).")", Logger::DEBUG);
		}

		Logger::log("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." - done in ".number_format($duration, 4)." seconds. Process PID: ".$new_process_id);

		// Write down the performance values into the log
		if (Config::get("system", "profiler")) {
			$duration = microtime(true)-$a->performance["start"];

			$o = '';
			if (Config::get("rendertime", "callstack")) {
				if (isset($a->callstack["database"])) {
					$o .= "\nDatabase Read:\n";
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
				if (isset($a->callstack["dache"])) {
					$o .= "\nCache Read:\n";
					foreach ($a->callstack["dache"] as $func => $time) {
						$time = round($time, 3);
						if ($time > 0) {
							$o .= $func.": ".$time."\n";
						}
					}
				}
				if (isset($a->callstack["dache_write"])) {
					$o .= "\nCache Write:\n";
					foreach ($a->callstack["dache_write"] as $func => $time) {
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
			}

			Logger::log(
				"ID ".$queue["id"].": ".$funcname.": ".sprintf(
					"DB: %s/%s, Cache: %s/%s, Net: %s, I/O: %s, Other: %s, Total: %s".$o,
					number_format($a->performance["database"] - $a->performance["database_write"], 2),
					number_format($a->performance["database_write"], 2),
					number_format($a->performance["cache"], 2),
					number_format($a->performance["cache_write"], 2),
					number_format($a->performance["network"], 2),
					number_format($a->performance["file"], 2),
					number_format($duration - ($a->performance["database"]
						+ $a->performance["cache"] + $a->performance["cache_write"]
						+ $a->performance["network"] + $a->performance["file"]), 2),
					number_format($duration, 2)
				),
				Logger::DEBUG
			);
		}

		$cooldown = Config::get("system", "worker_cooldown", 0);

		if ($cooldown > 0) {
			Logger::log("Process ".$mypid." - Prio ".$queue["priority"]." - ID ".$queue["id"].": ".$funcname." - in cooldown for ".$cooldown." seconds");
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
	 * @param integer $entries Total number of queue entries
	 * @param integer $deferred Number of deferred queue entries
	 *
	 * @return bool Are there too much workers running?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function tooMuchWorkers($entries = 0, $deferred = 0)
	{
		$queues = Config::get("system", "worker_queues", 4);

		$maxqueues = $queues;

		$active = self::activeWorkers();

		// Decrease the number of workers at higher load
		$load = System::currentLoad();
		if ($load) {
			$maxsysload = intval(Config::get("system", "maxloadavg", 50));

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

			if (empty($deferred) && empty($entries)) {
				$deferred = self::deferredEntries();
				$entries = max(self::totalEntries() - $deferred, 0);
			}

			$waiting_processes = max(0, $entries - $deferred);

			if (Config::get('system', 'worker_debug')) {
				$waiting_processes = 0;
				// Now adding all processes with workerqueue entries
				$stamp = (float)microtime(true);
				$jobs = DBA::p("SELECT COUNT(*) AS `entries`, `priority` FROM `workerqueue` WHERE NOT `done` AND `next_try` < ? GROUP BY `priority`", DateTimeFormat::utcNow());
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
				$stamp = (float)microtime(true);
				$jobs = DBA::p("SELECT COUNT(*) AS `running`, `priority` FROM `process` INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` AND NOT `done` GROUP BY `priority` ORDER BY `priority`");
				self::$db_duration += (microtime(true) - $stamp);

				while ($entry = DBA::fetch($jobs)) {
					$idle_workers -= $entry["running"];
					$listitem[$entry["priority"]] = $entry["priority"].":".$entry["running"];
				}
				DBA::close($jobs);
			}

			$listitem[0] = "0:" . max(0, $idle_workers);

			$processlist .= ' ('.implode(', ', $listitem).')';

			if (Config::get("system", "worker_fastlane", false) && ($queues > 0) && self::entriesExists() && ($active >= $queues)) {
				$top_priority = self::highestPriority();
				$high_running = self::processWithPriorityActive($top_priority);

				if (!$high_running && ($top_priority > PRIORITY_UNDEFINED) && ($top_priority < PRIORITY_NEGLIGIBLE)) {
					Logger::log("There are jobs with priority ".$top_priority." waiting but none is executed. Open a fastlane.", Logger::DEBUG);
					$queues = $active + 1;
				}
			}

			Logger::log("Load: " . $load ."/" . $maxsysload . " - processes: " . $deferred . "/" . $active . "/" . $waiting_processes . $processlist . " - maximum: " . $queues . "/" . $maxqueues, Logger::DEBUG);

			// Are there fewer workers running as possible? Then fork a new one.
			if (!Config::get("system", "worker_dont_fork", false) && ($queues > ($active + 1)) && ($entries > 1)) {
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
	 * @brief Check if we should pass some slow processes
	 *
	 * When the active processes of the highest priority are using more than 2/3
	 * of all processes, we let pass slower processes.
	 *
	 * @param string $highest_priority Returns the currently highest priority
	 * @return bool We let pass a slower process than $highest_priority
	 * @throws \Exception
	 */
	private static function passingSlow(&$highest_priority)
	{
		$highest_priority = 0;

		$stamp = (float)microtime(true);
		$r = DBA::p(
			"SELECT `priority`
				FROM `process`
				INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` AND NOT `done`"
		);
		self::$db_duration += (microtime(true) - $stamp);

		// No active processes at all? Fine
		if (!DBA::isResult($r)) {
			return false;
		}
		$priorities = [];
		while ($line = DBA::fetch($r)) {
			$priorities[] = $line["priority"];
		}
		DBA::close($r);

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
		Logger::log("Highest priority: ".$highest_priority." Total processes: ".count($priorities)." Count high priority processes: ".$high, Logger::DEBUG);
		$passing_slow = (($high/count($priorities)) > (2/3));

		if ($passing_slow) {
			Logger::log("Passing slower processes than priority ".$highest_priority, Logger::DEBUG);
		}
		return $passing_slow;
	}

	/**
	 * @brief Find and claim the next worker process for us
	 *
	 * @param boolean $passing_slow Returns if we had passed low priority processes
	 * @param integer $entries Total number of queue entries
	 * @param integer $deferred Number of deferred queue entries
	 * @return boolean Have we found something?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function findWorkerProcesses(&$passing_slow, $entries, $deferred)
	{
		$mypid = getmypid();

		// Check if we should pass some low priority process
		$highest_priority = 0;
		$found = false;
		$passing_slow = false;

		// The higher the number of parallel workers, the more we prefetch to prevent concurring access
		// We decrease the limit with the number of entries left in the queue
		$worker_queues = Config::get("system", "worker_queues", 4);
		$queue_length = Config::get('system', 'worker_fetch_limit', 1);
		$lower_job_limit = $worker_queues * $queue_length * 2;
		$entries = max($entries - $deferred, 0);

		// Now do some magic
		$exponent = 2;
		$slope = $queue_length / pow($lower_job_limit, $exponent);
		$limit = min($queue_length, ceil($slope * pow($entries, $exponent)));

		Logger::log('Deferred: ' . $deferred . ' - Total: ' . $entries . ' - Maximum: ' . $queue_length . ' - jobs per queue: ' . $limit, Logger::DEBUG);
		$ids = [];
		if (self::passingSlow($highest_priority)) {
			// Are there waiting processes with a higher priority than the currently highest?
			$stamp = (float)microtime(true);
			$result = DBA::select(
				'workerqueue',
				['id'],
				["`pid` = 0 AND `priority` < ? AND NOT `done` AND `next_try` < ?",
				$highest_priority, DateTimeFormat::utcNow()],
				['limit' => 1, 'order' => ['priority', 'created']]
			);
			self::$db_duration += (microtime(true) - $stamp);

			while ($id = DBA::fetch($result)) {
				$ids[] = $id["id"];
			}
			DBA::close($result);

			$found = (count($ids) > 0);

			if (!$found) {
				// Give slower processes some processing time
				$stamp = (float)microtime(true);
				$result = DBA::select(
					'workerqueue',
					['id'],
					["`pid` = 0 AND `priority` > ? AND NOT `done` AND `next_try` < ?",
					$highest_priority, DateTimeFormat::utcNow()],
					['limit' => 1, 'order' => ['priority', 'created']]
				);
				self::$db_duration += (microtime(true) - $stamp);

				while ($id = DBA::fetch($result)) {
					$ids[] = $id["id"];
				}
				DBA::close($result);

				$found = (count($ids) > 0);
				$passing_slow = $found;
			}
		}

		// At first try to fetch a bunch of high or medium tasks
		if (!$found && ($limit > 1)) {
			$stamp = (float)microtime(true);
			$result = DBA::select(
				'workerqueue',
				['id'],
				["`pid` = 0 AND NOT `done` AND `priority` <= ? AND `next_try` < ? AND `retrial` = 0",
				PRIORITY_MEDIUM, DateTimeFormat::utcNow()],
				['limit' => $limit, 'order' => ['created']]
			);
			self::$db_duration += (microtime(true) - $stamp);

			while ($id = DBA::fetch($result)) {
				$ids[] = $id["id"];
			}
			DBA::close($result);

			$found = (count($ids) > 0);
		}

		// If there is no result (or we shouldn't pass lower processes) we check without priority limit
		if (!$found) {
			$stamp = (float)microtime(true);
			$result = DBA::select(
				'workerqueue',
				['id'],
				["`pid` = 0 AND NOT `done` AND `next_try` < ?",
				DateTimeFormat::utcNow()],
				['limit' => 1, 'order' => ['priority', 'created']]
			);
			self::$db_duration += (microtime(true) - $stamp);

			while ($id = DBA::fetch($result)) {
				$ids[] = $id["id"];
			}
			DBA::close($result);

			$found = (count($ids) > 0);
		}

		if ($found) {
			$stamp = (float)microtime(true);
			$condition = "`id` IN (".substr(str_repeat("?, ", count($ids)), 0, -2).") AND `pid` = 0 AND NOT `done`";
			array_unshift($ids, $condition);
			DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow(), 'pid' => $mypid], $ids);
			self::$db_duration += (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		}

		return $found;
	}

	/**
	 * @brief Returns the next worker process
	 *
	 * @param boolean $passing_slow Returns if we had passed low priority processes
	 * @param integer $entries Returns total number of queue entries
	 * @param integer $deferred Returns number of deferred queue entries
	 *
	 * @return string SQL statement
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function workerProcess(&$passing_slow, &$entries, &$deferred)
	{
		// There can already be jobs for us in the queue.
		$stamp = (float)microtime(true);
		$r = DBA::select('workerqueue', [], ['pid' => getmypid(), 'done' => false]);
		self::$db_duration += (microtime(true) - $stamp);
		if (DBA::isResult($r)) {
			return DBA::toArray($r);
		}
		DBA::close($r);

		// Counting the rows outside the lock reduces the lock time
		$entries = self::totalEntries();
		$deferred = self::deferredEntries();

		$stamp = (float)microtime(true);
		if (!Lock::acquire('worker_process')) {
			return false;
		}
		self::$lock_duration += (microtime(true) - $stamp);

		$found = self::findWorkerProcesses($passing_slow, $entries, $deferred);

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
		Network::fetchUrl($url, false, $redirects, 1);
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

		get_app()->proc_run($command, $args);

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
	 * or: Worker::add(PRIORITY_HIGH, "Notifier", "drop", $drop_id);
	 * or: Worker::add(array('priority' => PRIORITY_HIGH, 'dont_fork' => true), "CreateShadowEntry", $post_id);
	 *
	 * @return boolean "false" if proc_run couldn't be executed
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
		$dont_fork = Config::get("system", "worker_dont_fork", false);
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

		// Quit if there was a database error - a precaution for the update process to 3.5.3
		if (DBA::errorNo() != 0) {
			return false;
		}

		if (!$found) {
			DBA::insert('workerqueue', ['parameter' => $parameters, 'created' => $created, 'priority' => $priority]);
		} elseif ($force_priority) {
			DBA::update('workerqueue', ['priority' => $priority], ['parameter' => $parameters, 'done' => false, 'pid' => 0]);
		}

		// Should we quit and wait for the worker to be called as a cronjob?
		if ($dont_fork) {
			return true;
		}

		// If there is a lock then we don't have to check for too much worker
		if (!Lock::acquire('worker', 0)) {
			return true;
		}

		// If there are already enough workers running, don't fork another one
		$quit = self::tooMuchWorkers();
		Lock::release('worker');

		if ($quit) {
			return true;
		}

		// We tell the daemon that a new job entry exists
		if (Config::get('system', 'worker_daemon_mode', false)) {
			// We don't have to set the IPC flag - this is done in "tooMuchWorkers"
			return true;
		}

		// Now call the worker to execute the jobs that we just added to the queue
		self::spawnWorker();

		return true;
	}

	/**
	 * Defers the current worker entry
	 */
	public static function defer()
	{
		if (empty(BaseObject::getApp()->queue)) {
			return;
		}

		$queue = BaseObject::getApp()->queue;

		$retrial = $queue['retrial'];
		$id = $queue['id'];
		$priority = $queue['priority'];

		if ($retrial > 14) {
			Logger::log('Id ' . $id . ' had been tried 14 times. We stop now.', Logger::DEBUG);
			return;
		}

		// Calculate the delay until the next trial
		$delay = (($retrial + 3) ** 4) + (rand(1, 30) * ($retrial + 1));
		$next = DateTimeFormat::utc('now + ' . $delay . ' seconds');

		if (($priority < PRIORITY_MEDIUM) && ($retrial > 2)) {
			$priority = PRIORITY_MEDIUM;
		} elseif (($priority < PRIORITY_LOW) && ($retrial > 5)) {
			$priority = PRIORITY_LOW;
		} elseif (($priority < PRIORITY_NEGLIGIBLE) && ($retrial > 7)) {
			$priority = PRIORITY_NEGLIGIBLE;
		}

		Logger::log('Defer execution ' . $retrial . ' of id ' . $id . ' to ' . $next . ' - priority old/new: ' . $queue['priority'] . '/' . $priority, Logger::DEBUG);

		$stamp = (float)microtime(true);
		$fields = ['retrial' => $retrial + 1, 'next_try' => $next, 'executed' => DBA::NULL_DATETIME, 'pid' => 0, 'priority' => $priority];
		DBA::update('workerqueue', $fields, ['id' => $id]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
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
