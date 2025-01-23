<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Type\WorkerLogger;
use Friendica\Core\Worker\Entity\Process;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Contains the class for the worker background job processing
 */
class Worker
{
	/**
	 * @name Priority
	 *
	 * Process priority for the worker
	 * @{
	 */
	const PRIORITY_UNDEFINED  = 0;
	const PRIORITY_CRITICAL   = 10;
	const PRIORITY_HIGH       = 20;
	const PRIORITY_MEDIUM     = 30;
	const PRIORITY_LOW        = 40;
	const PRIORITY_NEGLIGIBLE = 50;
	const PRIORITIES          = [self::PRIORITY_CRITICAL, self::PRIORITY_HIGH, self::PRIORITY_MEDIUM, self::PRIORITY_LOW, self::PRIORITY_NEGLIGIBLE];
	/* @}*/

	const STATE_STARTUP    = 1; // Worker is in startup. This takes most time.
	const STATE_LONG_LOOP  = 2; // Worker is processing the whole - long - loop.
	const STATE_REFETCH    = 3; // Worker had refetched jobs in the execution loop.
	const STATE_SHORT_LOOP = 4; // Worker is processing preassigned jobs, thus saving much time.

	const FAST_COMMANDS = ['APDelivery', 'Delivery'];

	const LOCK_PROCESS = 'worker_process';
	const LOCK_WORKER  = 'worker';
	const LAST_CHECK   = 'worker::check';

	private static $up_start;
	private static $db_duration       = 0;
	private static $db_duration_count = 0;
	private static $db_duration_write = 0;
	private static $db_duration_stat  = 0;
	private static $lock_duration     = 0;
	private static $last_update;
	private static $state;
	/** @var Process */
	private static $process;

	/**
	 * Processes the tasks that are in the workerqueue table
	 *
	 * @param boolean $run_cron Should the cron processes be executed?
	 * @param Process $process  The current running process
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function processQueue(bool $run_cron, Process $process)
	{
		self::$up_start = microtime(true);

		// At first check the maximum load. We shouldn't continue with a high load
		if (DI::system()->isMaxLoadReached()) {
			DI::logger()->notice('Pre check: maximum load reached, quitting.');
			return;
		}

		// We now start the process. This is done after the load check since this could increase the load.
		self::$process = $process;

		// Kill stale processes every 5 minutes
		$last_cleanup = DI::keyValue()->get('worker_last_cleaned') ?? 0;
		if (time() > ($last_cleanup + 300)) {
			DI::keyValue()->set( 'worker_last_cleaned', time());
			Worker\Cron::killStaleWorkers();
		}

		// Check if the system is ready
		if (!self::isReady()) {
			return;
		}

		// Now we start additional cron processes if we should do so
		if ($run_cron) {
			Worker\Cron::run();
		}

		$last_check  = $starttime = time();
		self::$state = self::STATE_STARTUP;

		// We fetch the next queue entry that is about to be executed
		while ($r = self::workerProcess()) {
			if (Worker\IPC::JobsExists(getmypid())) {
				Worker\IPC::DeleteJobState(getmypid());
			}

			// Don't refetch when a worker fetches tasks for multiple workers
			$refetched = DI::config()->get('system', 'worker_multiple_fetch');
			foreach ($r as $entry) {
				// The work will be done
				if (!self::execute($entry)) {
					DI::logger()->warning('Process execution failed, quitting.', ['entry' => $entry]);
					return;
				}

				// Trying to fetch new processes - but only once when successful
				if (!$refetched && DI::lock()->acquire(self::LOCK_PROCESS, 0)) {
					self::findWorkerProcesses();
					DI::lock()->release(self::LOCK_PROCESS);
					self::$state = self::STATE_REFETCH;
					$refetched   = true;
				} else {
					self::$state = self::STATE_SHORT_LOOP;
				}
			}

			// To avoid the quitting of multiple workers only one worker at a time will execute the check
			if ((time() > $last_check + 5) && !self::getWaitingJobForPID()) {
				self::$state = self::STATE_LONG_LOOP;

				if (DI::lock()->acquire(self::LOCK_WORKER, 0)) {
					// Count active workers and compare them with a maximum value that depends on the load
					if (self::tooMuchWorkers()) {
						DI::logger()->info('Active worker limit reached, quitting.');
						DI::lock()->release(self::LOCK_WORKER);
						return;
					}

					// Check free memory
					if (DI::system()->isMinMemoryReached()) {
						DI::logger()->warning('Memory limit reached, quitting.');
						DI::lock()->release(self::LOCK_WORKER);
						return;
					}
					DI::lock()->release(self::LOCK_WORKER);
				}
				$last_check = time();
			}

			// Quit the worker once every cron interval
			if (time() > ($starttime + (DI::config()->get('system', 'cron_interval') * 60)) && !self::systemLimitReached()) {
				DI::logger()->info('Process lifetime reached, respawning.');
				self::unclaimProcess($process);
				if (Worker\Daemon::isMode()) {
					Worker\IPC::SetJobState(true);
				} else {
					self::spawnWorker();
				}
				return;
			}
		}

		// Cleaning up. Possibly not needed, but it doesn't harm anything.
		if (Worker\Daemon::isMode()) {
			Worker\IPC::SetJobState(false);
		}
		DI::logger()->info("Couldn't select a workerqueue entry, quitting process", ['pid' => getmypid()]);
	}

	/**
	 * Checks if the system is ready.
	 *
	 * Several system parameters like memory, connections and processes are checked.
	 *
	 * @return boolean
	 */
	public static function isReady(): bool
	{
		// Count active workers and compare them with a maximum value that depends on the load
		if (self::tooMuchWorkers()) {
			DI::logger()->info('Active worker limit reached, quitting.');
			return false;
		}

		// Do we have too few memory?
		if (DI::system()->isMinMemoryReached()) {
			DI::logger()->warning('Memory limit reached, quitting.');
			return false;
		}

		// Possibly there are too much database connections
		if (self::maxConnectionsReached()) {
			DI::logger()->warning('Maximum connections reached, quitting.');
			return false;
		}

		// Possibly there are too much database processes that block the system
		if (DI::system()->isMaxProcessesReached()) {
			DI::logger()->warning('Maximum processes reached, quitting.');
			return false;
		}

		return true;
	}

	/**
	 * Check if non executed tasks do exist in the worker queue
	 *
	 * @return boolean Returns "true" if tasks are existing
	 * @throws \Exception
	 */
	public static function entriesExists(): bool
	{
		$stamp  = (float)microtime(true);
		$exists = DBA::exists('workerqueue', ["NOT `done` AND `pid` = 0 AND `next_try` < ?", DateTimeFormat::utcNow()]);
		self::$db_duration += (microtime(true) - $stamp);
		return $exists;
	}

	/**
	 * Returns the number of deferred entries in the worker queue
	 *
	 * @return integer Number of deferred entries in the worker queue
	 * @throws \Exception
	 */
	public static function deferredEntries(): int
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('workerqueue', ["NOT `done` AND `pid` = 0 AND `retrial` > ?", 0]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * Returns the number of non executed entries in the worker queue
	 *
	 * @return integer Number of non executed entries in the worker queue
	 * @throws \Exception
	 */
	public static function totalEntries(): int
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('workerqueue', ['done' => false, 'pid' => 0]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * Returns the highest priority in the worker queue that isn't executed
	 *
	 * @return integer Number of active worker processes
	 * @throws \Exception
	 */
	private static function highestPriority(): int
	{
		$stamp       = (float)microtime(true);
		$condition   = ["`pid` = 0 AND NOT `done` AND `next_try` < ?", DateTimeFormat::utcNow()];
		$workerqueue = DBA::selectFirst('workerqueue', ['priority'], $condition, ['order' => ['priority']]);
		self::$db_duration += (microtime(true) - $stamp);
		if (DBA::isResult($workerqueue)) {
			return $workerqueue['priority'];
		} else {
			return 0;
		}
	}

	/**
	 * Returns if a process with the given priority is running
	 *
	 * @param integer $priority The priority that should be checked
	 *
	 * @return integer Is there a process running with that priority?
	 * @throws \Exception
	 */
	private static function processWithPriorityActive(int $priority): int
	{
		$condition = ["`priority` <= ? AND `pid` != 0 AND NOT `done`", $priority];
		return DBA::exists('workerqueue', $condition);
	}

	/**
	 * Checks if the given file is valid to be included
	 *
	 * @param mixed $file
	 * @return bool
	 */
	private static function validateInclude(&$file): bool
	{
		$orig_file = $file;

		$file = realpath($file);

		if (strpos($file, getcwd()) !== 0) {
			return false;
		}

		$file = str_replace(getcwd() . '/', '', $file, $count);
		if ($count != 1) {
			return false;
		}

		if ($orig_file !== $file) {
			return false;
		}

		return (strpos($file, 'addon/') === 0);
	}

	/**
	 * Execute a worker entry
	 *
	 * @param array $queue Workerqueue entry
	 *
	 * @return boolean "true" if further processing should be stopped
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function execute(array $queue): bool
	{
		$mypid = getmypid();

		// Quit when in maintenance
		if (DI::config()->get('system', 'maintenance', false)) {
			DI::logger()->notice('Maintenance mode - quit process', ['pid' => $mypid]);
			return false;
		}

		// Constantly check the number of parallel database processes
		if (DI::system()->isMaxProcessesReached()) {
			DI::logger()->warning('Max processes reached for process', ['pid' => $mypid]);
			return false;
		}

		// Constantly check the number of available database connections to let the frontend be accessible at any time
		if (self::maxConnectionsReached()) {
			DI::logger()->warning('Max connection reached for process', ['pid' => $mypid]);
			return false;
		}

		$argv = json_decode($queue['parameter'], true);
		if (!is_array($argv)) {
			$argv = [];
		}

		if (!empty($queue['command'])) {
			array_unshift($argv, $queue['command']);
		}

		if (empty($argv)) {
			DI::logger()->warning('Parameter is empty', ['queue' => $queue]);
			return false;
		}

		// Check for existence and validity of the include file
		$include = $argv[0];

		if (method_exists(sprintf('Friendica\Worker\%s', $include), 'execute')) {
			// We constantly update the "executed" date every minute to avoid being killed too soon
			if (!isset(self::$last_update)) {
				self::$last_update = strtotime($queue['executed']);
			}

			$age               = (time() - self::$last_update) / 60;
			self::$last_update = time();

			if ($age > 1) {
				$stamp = (float)microtime(true);
				DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow()], ['pid' => $mypid, 'done' => false]);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			}

			array_shift($argv);

			self::execFunction($queue, $include, $argv, true);

			$stamp     = (float)microtime(true);
			$condition = ["`id` = ? AND `next_try` < ?", $queue['id'], DateTimeFormat::utcNow()];
			if (DBA::update('workerqueue', ['done' => true], $condition)) {
				DI::keyValue()->set('last_worker_execution', DateTimeFormat::utcNow());
			}
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);

			return true;
		}

		if (!self::validateInclude($include)) {
			DI::logger()->warning('Include file is not valid', ['file' => $argv[0]]);
			$stamp = (float)microtime(true);
			DBA::delete('workerqueue', ['id' => $queue['id']]);
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
			return true;
		}

		require_once $include;

		$funcname = str_replace('.php', '', basename($argv[0])) .'_run';

		if (function_exists($funcname)) {
			// We constantly update the "executed" date every minute to avoid being killed too soon
			if (!isset(self::$last_update)) {
				self::$last_update = strtotime($queue['executed']);
			}

			$age               = (time() - self::$last_update) / 60;
			self::$last_update = time();

			if ($age > 1) {
				$stamp = (float)microtime(true);
				DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow()], ['pid' => $mypid, 'done' => false]);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			}

			self::execFunction($queue, $funcname, $argv, false);

			$stamp = (float)microtime(true);
			if (DBA::update('workerqueue', ['done' => true], ['id' => $queue['id']])) {
				DI::keyValue()->set('last_worker_execution', DateTimeFormat::utcNow());
			}
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		} else {
			DI::logger()->warning('Function does not exist', ['function' => $funcname]);
			$stamp = (float)microtime(true);
			DBA::delete('workerqueue', ['id' => $queue['id']]);
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		}

		return true;
	}

	/**
	 * Checks if system limits are reached.
	 *
	 * @return boolean
	 */
	private static function systemLimitReached(): bool
	{
		$load_cooldown      = DI::config()->get('system', 'worker_load_cooldown');
		$processes_cooldown = DI::config()->get('system', 'worker_processes_cooldown');

		if ($load_cooldown == 0) {
			$load_cooldown = DI::config()->get('system', 'maxloadavg');
		}

		if (($load_cooldown == 0) && ($processes_cooldown == 0)) {
			return false;
		}

		$load = System::getLoadAvg($processes_cooldown != 0);
		if (empty($load)) {
			return false;
		}

		if (($load_cooldown > 0) && ($load['average1'] > $load_cooldown)) {
			return true;
		}

		if (($processes_cooldown > 0) && ($load['scheduled'] > $processes_cooldown)) {
			return true;
		}

		return false;
	}

	/**
	 * Slow the execution down if the system load is too high
	 *
	 * @return void
	 */
	public static function coolDown()
	{
		$cooldown = DI::config()->get('system', 'worker_cooldown', 0);
		if ($cooldown > 0) {
			DI::logger()->debug('Wait for cooldown.', ['cooldown' => $cooldown]);
			if ($cooldown < 1) {
				usleep($cooldown * 1000000);
			} else {
				sleep($cooldown);
			}
		}

		$load_cooldown      = DI::config()->get('system', 'worker_load_cooldown');
		$processes_cooldown = DI::config()->get('system', 'worker_processes_cooldown');

		if ($load_cooldown == 0) {
			$load_cooldown = DI::config()->get('system', 'maxloadavg');
		}

		if (($load_cooldown == 0) && ($processes_cooldown == 0)) {
			return;
		}

		$sleeping = false;

		while ($load = System::getLoadAvg($processes_cooldown != 0)) {
			if (($load_cooldown > 0) && ($load['average1'] > $load_cooldown)) {
				if (!$sleeping) {
					DI::logger()->info('Load induced pre execution cooldown.', ['max' => $load_cooldown, 'load' => $load, 'called-by' => System::callstack(1)]);
					$sleeping = true;
				}
				sleep(1);
				continue;
			}
			if (($processes_cooldown > 0) && ($load['scheduled'] > $processes_cooldown)) {
				if (!$sleeping) {
					DI::logger()->info('Process induced pre execution cooldown.', ['max' => $processes_cooldown, 'load' => $load, 'called-by' => System::callstack(1)]);
					$sleeping = true;
				}
				sleep(1);
				continue;
			}
			break;
		}

		if ($sleeping) {
			DI::logger()->info('Cooldown ended.', ['max-load' => $load_cooldown, 'max-processes' => $processes_cooldown, 'load' => $load, 'called-by' => System::callstack(1)]);
		}
	}

	/**
	 * Execute a function from the queue
	 *
	 * @param array   $queue       Workerqueue entry
	 * @param string  $funcname    name of the function
	 * @param array   $argv        Array of values to be passed to the function
	 * @param boolean $method_call boolean
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function execFunction(array $queue, string $funcname, array $argv, bool $method_call)
	{
		$appHelper = DI::appHelper();

		self::coolDown();

		DI::loggerManager()->changeLogChannel(LogChannel::WORKER);

		$logger = DI::logger();

		if ($logger instanceof WorkerLogger) {
			$logger->setFunctionName($funcname);
		}

		DI::logger()->info('Process start.', ['priority' => $queue['priority'], 'id' => $queue['id']]);

		$stamp = (float)microtime(true);

		// We use the callstack here to analyze the performance of executed worker entries.
		// For this reason the variables have to be initialized.
		DI::profiler()->reset();

		$appHelper->setQueue($queue);

		$up_duration = microtime(true) - self::$up_start;

		// Reset global data to avoid interferences
		unset($_SESSION);

		// Set the workerLogger as new default logger
		if ($method_call) {
			try {
				call_user_func_array(sprintf('Friendica\Worker\%s::execute', $funcname), $argv);
			} catch (\Throwable $e) {
				DI::logger()->error('Uncaught exception in worker method execution', ['class' => get_class($e), 'message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile() . ':' . $e->getLine(), 'trace' => $e->getTraceAsString(), 'previous' => $e->getPrevious()]);
				Worker::defer();
			}
		} else {
			try {
				$funcname($argv, count($argv));
			} catch (\Throwable $e) {
				DI::logger()->error('Uncaught exception in worker execution', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile() . ':' . $e->getLine(), 'trace' => $e->getTraceAsString(), 'previous' => $e->getPrevious()]);
				Worker::defer();
			}
		}

		DI::loggerManager()->changeLogChannel(LogChannel::DEFAULT);

		$appHelper->setQueue([]);

		$duration = (microtime(true) - $stamp);

		/* With these values we can analyze how effective the worker is.
		 * The database and rest time should be low since this is the unproductive time.
		 * The execution time is the productive time.
		 * By changing parameters like the maximum number of workers we can check the effectiveness.
		*/
		$dbtotal = round(self::$db_duration, 2);
		$dbread  = round(self::$db_duration - (self::$db_duration_count + self::$db_duration_write + self::$db_duration_stat), 2);
		$dbcount = round(self::$db_duration_count, 2);
		$dbstat  = round(self::$db_duration_stat, 2);
		$dbwrite = round(self::$db_duration_write, 2);
		$dblock  = round(self::$lock_duration, 2);
		$rest    = round(max(0, $up_duration - (self::$db_duration + self::$lock_duration)), 2);
		$exec    = round($duration, 2);

		DI::logger()->info('Performance:', ['function' => $funcname, 'state' => self::$state, 'count' => $dbcount, 'stat' => $dbstat, 'write' => $dbwrite, 'lock' => $dblock, 'total' => $dbtotal, 'rest' => $rest, 'exec' => $exec]);

		self::coolDown();

		self::$up_start          = microtime(true);
		self::$db_duration       = 0;
		self::$db_duration_count = 0;
		self::$db_duration_stat  = 0;
		self::$db_duration_write = 0;
		self::$lock_duration     = 0;

		if ($duration > 3600) {
			DI::logger()->info('Longer than 1 hour.', ['priority' => $queue['priority'], 'id' => $queue['id'], 'duration' => round($duration / 60, 3)]);
		} elseif ($duration > 600) {
			DI::logger()->info('Longer than 10 minutes.', ['priority' => $queue['priority'], 'id' => $queue['id'], 'duration' => round($duration / 60, 3)]);
		} elseif ($duration > 300) {
			DI::logger()->info('Longer than 5 minutes.', ['priority' => $queue['priority'], 'id' => $queue['id'], 'duration' => round($duration / 60, 3)]);
		} elseif ($duration > 120) {
			DI::logger()->info('Longer than 2 minutes.', ['priority' => $queue['priority'], 'id' => $queue['id'], 'duration' => round($duration / 60, 3)]);
		}

		DI::logger()->info('Process done.', ['function' => $funcname, 'priority' => $queue['priority'], 'retrial' => $queue['retrial'], 'id' => $queue['id'], 'duration' => round($duration, 3)]);

		DI::profiler()->saveLog(DI::logger(), 'ID ' . $queue['id'] . ': ' . $funcname);
	}

	/**
	 * Checks if the number of database connections has reached a critical limit.
	 *
	 * @return bool Are more than 3/4 of the maximum connections used?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function maxConnectionsReached(): bool
	{
		// Fetch the max value from the config. This is needed when the system cannot detect the correct value by itself.
		$max = DI::config()->get('system', 'max_connections');

		// Fetch the percentage level where the worker will get active
		$maxlevel = DI::config()->get('system', 'max_connections_level', 75);

		if ($max == 0) {
			// the maximum number of possible user connections can be a system variable
			$r = DBA::fetchFirst("SHOW VARIABLES WHERE `variable_name` = 'max_user_connections'");
			if (DBA::isResult($r)) {
				$max = $r['Value'];
			}
			// Or it can be granted. This overrides the system variable
			$stamp = (float)microtime(true);
			$r     = DBA::p('SHOW GRANTS');
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

		$stamp = (float)microtime(true);
		$used  = 0;
		$sleep = 0;
		$data  = DBA::p("SHOW PROCESSLIST");
		while ($row = DBA::fetch($data)) {
			if ($row['Command'] != 'Sleep') {
				++$used;
			} else {
				++$sleep;
			}
		}
		DBA::close($data);
		self::$db_duration += (microtime(true) - $stamp);

		// If $max is set we will use the processlist to determine the current number of connections
		// The processlist only shows entries of the current user
		if ($max != 0) {
			DI::logger()->info('Connection usage (user values)', ['working' => $used, 'sleeping' => $sleep, 'max' => $max]);

			$level = ($used / $max) * 100;

			if ($level >= $maxlevel) {
				DI::logger()->warning('Maximum level (' . $maxlevel . '%) of user connections reached: ' . $used .'/' . $max);
				return true;
			}
		}

		// We will now check for the system values.
		// This limit could be reached although the user limits are fine.
		$r = DBA::fetchFirst("SHOW VARIABLES WHERE `variable_name` = 'max_connections'");
		if (!DBA::isResult($r)) {
			return false;
		}
		$max = intval($r['Value']);
		if ($max == 0) {
			return false;
		}
		$r = DBA::fetchFirst("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
		if (!DBA::isResult($r)) {
			return false;
		}
		$used = max($used, intval($r['Value'])) - $sleep;
		if ($used == 0) {
			return false;
		}
		DI::logger()->info('Connection usage (system values)', ['working' => $used, 'sleeping' => $sleep, 'max' => $max]);

		$level = $used / $max * 100;

		if ($level < $maxlevel) {
			return false;
		}
		DI::logger()->warning('Maximum level (' . $level . '%) of system connections reached: ' . $used . '/' . $max);
		return true;
	}


	/**
	 * Checks if the number of active workers exceeds the given limits
	 *
	 * @return bool Are there too much workers running?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function tooMuchWorkers(): bool
	{
		$queues = DI::config()->get('system', 'worker_queues', 10);

		$maxqueues = $queues;

		$active = self::activeWorkers();

		// Decrease the number of workers at higher load
		$load = System::currentLoad();
		if ($load) {
			$maxsysload = intval(DI::config()->get('system', 'maxloadavg', 20));

			/* Default exponent 3 causes queues to rapidly decrease as load increases.
			 * If you have 20 max queues at idle, then you get only 5 queues at 37.1% of $maxsysload.
			 * For some environments, this rapid decrease is not needed.
			 * With exponent 1, you could have 20 max queues at idle and 13 at 37% of $maxsysload.
			 */
			$exponent = intval(DI::config()->get('system', 'worker_load_exponent', 3));
			$slope    = pow(max(0, $maxsysload - $load) / $maxsysload, $exponent);
			$queues   = intval(ceil($slope * $maxqueues));

			$processlist = '';

			if (DI::config()->get('system', 'worker_jpm')) {
				$intervals       = explode(',', DI::config()->get('system', 'worker_jpm_range'));
				$jobs_per_minute = [];
				foreach ($intervals as $interval) {
					if ($interval == 0) {
						continue;
					} else {
						$interval = (int)$interval;
					}

					$stamp = (float)microtime(true);
					$jobs  = DBA::count('workerqueue', ["`done` AND `executed` > ?", DateTimeFormat::utc('now - ' . $interval . ' minute')]);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_stat += (microtime(true) - $stamp);
					$jobs_per_minute[$interval] = number_format($jobs / $interval, 0);
				}
				$processlist = ' - jpm: '.implode('/', $jobs_per_minute);
			}

			// Create a list of queue entries grouped by their priority
			$listitem = [0 => ''];

			$idle_workers = $active;

			$deferred = self::deferredEntries();

			if (DI::config()->get('system', 'worker_debug')) {
				$waiting_processes = 0;
				// Now adding all processes with workerqueue entries
				$stamp = (float)microtime(true);
				$jobs  = DBA::p("SELECT COUNT(*) AS `entries`, `priority` FROM `workerqueue` WHERE NOT `done` GROUP BY `priority`");
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_stat += (microtime(true) - $stamp);
				while ($entry = DBA::fetch($jobs)) {
					$stamp   = (float)microtime(true);
					$running = DBA::count('workerqueue-view', ['priority' => $entry['priority']]);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_stat += (microtime(true) - $stamp);
					$idle_workers -= $running;
					$waiting_processes += $entry['entries'];
					$listitem[$entry['priority']] = $entry['priority'] . ':' . $running . '/' . $entry['entries'];
				}
				DBA::close($jobs);
			} else {
				$waiting_processes = self::totalEntries();
				$stamp             = (float)microtime(true);
				$jobs              = DBA::p("SELECT COUNT(*) AS `running`, `priority` FROM `workerqueue-view` GROUP BY `priority` ORDER BY `priority`");
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_stat += (microtime(true) - $stamp);

				while ($entry = DBA::fetch($jobs)) {
					$idle_workers -= $entry['running'];
					$listitem[$entry['priority']] = $entry['priority'] . ':' . $entry['running'];
				}
				DBA::close($jobs);
			}

			$waiting_processes -= $deferred;

			$listitem[0] = '0:' . max(0, $idle_workers);

			$processlist .= ' ('.implode(', ', $listitem).')';

			if (DI::config()->get('system', 'worker_fastlane', false) && ($queues > 0) && ($active >= $queues) && self::entriesExists()) {
				$top_priority = self::highestPriority();
				$high_running = self::processWithPriorityActive($top_priority);

				if (!$high_running && ($top_priority > self::PRIORITY_UNDEFINED) && ($top_priority < self::PRIORITY_NEGLIGIBLE)) {
					DI::logger()->info('Jobs with a higher priority are waiting but none is executed. Open a fastlane.', ['priority' => $top_priority]);
					$queues = $active + 1;
				}
			}

			DI::logger()->info('Load: ' . $load . '/' . $maxsysload . ' - processes: ' . $deferred . '/' . $active . '/' . $waiting_processes . $processlist . ' - maximum: ' . $queues . '/' . $maxqueues);

			// Are there fewer workers running as possible? Then fork a new one.
			if (!DI::config()->get('system', 'worker_dont_fork', false) && ($queues > ($active + 1)) && self::entriesExists() && !self::systemLimitReached()) {
				DI::logger()->info('There are fewer workers as possible, fork a new worker.', ['active' => $active, 'queues' => $queues]);
				if (Worker\Daemon::isMode()) {
					Worker\IPC::SetJobState(true);
				} else {
					self::spawnWorker();
				}
			} elseif (($active > $queues) && ($active < $maxqueues) && ($load < $maxsysload)) {
				$max_idletime = DI::config()->get('system', 'worker_max_idletime');
				$last_check   = DI::cache()->get(self::LAST_CHECK);
				$last_date    = $last_check ? date('c', $last_check) : '';
				if (
					($max_idletime > 0)
					&& (time() > (int) $last_check + (int) $max_idletime)
					&& !DBA::exists('workerqueue', ["`done` AND `executed` > ?", DateTimeFormat::utc('now - ' . $max_idletime . ' second')])
				) {
					DI::cache()->set(self::LAST_CHECK, time(), Duration::HOUR);
					DI::logger()->info('The last worker execution had been too long ago.', ['last' => $last_check, 'last-check' => $last_date, 'seconds' => $max_idletime, 'load' => $load, 'max_load' => $maxsysload, 'active_worker' => $active, 'max_worker' => $maxqueues]);
					return false;
				} elseif ($max_idletime > 0) {
					DI::logger()->debug('Maximum idletime not reached.', ['last' => $last_check, 'last-check' => $last_date, 'seconds' => $max_idletime, 'load' => $load, 'max_load' => $maxsysload, 'active_worker' => $active, 'max_worker' => $maxqueues]);
				}
			}
		}

		// if there are too much worker, we don't spawn a new one.
		if (Worker\Daemon::isMode() && ($active > $queues)) {
			Worker\IPC::SetJobState(false);
		}

		return $active > $queues;
	}

	/**
	 * Returns the number of active worker processes
	 *
	 * @return integer Number of active worker processes
	 * @throws \Exception
	 */
	public static function activeWorkers(): int
	{
		$stamp = (float)microtime(true);
		$count = DI::process()->countCommand('Worker.php');
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * Returns the number of active worker processes
	 *
	 * @return array List of worker process ids
	 * @throws \Exception
	 */
	private static function getWorkerPIDList(): array
	{
		$ids   = [];
		$stamp = (float)microtime(true);

		$queues = DBA::p("SELECT `process`.`pid`, COUNT(`workerqueue`.`pid`) AS `entries` FROM `process`
			LEFT JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` AND NOT `workerqueue`.`done`
			GROUP BY `process`.`pid`");
		while ($queue = DBA::fetch($queues)) {
			$ids[$queue['pid']] = $queue['entries'];
		}
		DBA::close($queues);

		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $ids;
	}

	/**
	 * Returns waiting jobs for the current process id
	 *
	 * @return array|bool waiting workerqueue jobs or FALSE on failure
	 * @throws \Exception
	 */
	private static function getWaitingJobForPID()
	{
		$stamp = (float)microtime(true);
		$r     = DBA::select('workerqueue', [], ['pid' => getmypid(), 'done' => false]);
		self::$db_duration += (microtime(true) - $stamp);
		if (DBA::isResult($r)) {
			return DBA::toArray($r);
		}
		DBA::close($r);

		return false;
	}

	/**
	 * Returns the next jobs that should be executed
	 * @param int $limit
	 * @return array array with next jobs
	 * @throws \Exception
	 */
	private static function nextProcess(int $limit): array
	{
		$priority = self::nextPriority();
		if (empty($priority)) {
			DI::logger()->info('No tasks found');
			return [];
		}

		$ids       = [];
		$stamp     = (float)microtime(true);
		$condition = ["`priority` = ? AND `pid` = 0 AND NOT `done` AND `next_try` < ?", $priority, DateTimeFormat::utcNow()];
		$tasks     = DBA::select('workerqueue', ['id', 'command', 'parameter'], $condition, ['limit' => $limit, 'order' => ['retrial', 'created']]);
		self::$db_duration += (microtime(true) - $stamp);
		while ($task = DBA::fetch($tasks)) {
			$ids[] = $task['id'];
			// Only continue that loop while we are storing commands that can be processed quickly
			if (!empty($task['command'])) {
				$command = $task['command'];
			} else {
				$command = json_decode($task['parameter'])[0];
			}

			if (!in_array($command, self::FAST_COMMANDS)) {
				break;
			}
		}
		DBA::close($tasks);

		DI::logger()->info('Found:', ['priority' => $priority, 'id' => $ids]);
		return $ids;
	}

	/**
	 * Returns the priority of the next workerqueue job
	 *
	 * @return string|bool priority or FALSE on failure
	 * @throws \Exception
	 */
	private static function nextPriority()
	{
		$waiting    = [];
		$priorities = [self::PRIORITY_CRITICAL, self::PRIORITY_HIGH, self::PRIORITY_MEDIUM, self::PRIORITY_LOW, self::PRIORITY_NEGLIGIBLE];
		foreach ($priorities as $priority) {
			$stamp = (float)microtime(true);
			if (DBA::exists('workerqueue', ["`priority` = ? AND `pid` = 0 AND NOT `done` AND `next_try` < ?", $priority, DateTimeFormat::utcNow()])) {
				$waiting[$priority] = true;
			}
			self::$db_duration += (microtime(true) - $stamp);
		}

		if (!empty($waiting[self::PRIORITY_CRITICAL])) {
			return self::PRIORITY_CRITICAL;
		}

		$running       = [];
		$running_total = 0;
		$stamp         = (float)microtime(true);
		$processes     = DBA::p("SELECT COUNT(DISTINCT(`pid`)) AS `running`, `priority` FROM `workerqueue-view` GROUP BY `priority`");
		self::$db_duration += (microtime(true) - $stamp);
		while ($process = DBA::fetch($processes)) {
			$running[$process['priority']] = $process['running'];
			$running_total += $process['running'];
		}
		DBA::close($processes);

		foreach ($priorities as $priority) {
			if (!empty($waiting[$priority]) && empty($running[$priority])) {
				DI::logger()->info('No running worker found with priority {priority} - assigning it.', ['priority' => $priority]);
				return $priority;
			}
		}

		$active     = max(self::activeWorkers(), $running_total);
		$priorities = max(count($waiting), count($running));
		$exponent   = 2;

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
				DI::logger()->info('Priority {priority} has got {workers} workers out of a limit of {limit}', ['priority' => $priority, 'workers' => $workers, 'limit' => $limit[$i - 1]]);
				return $priority;
			}
		}

		if (!empty($waiting)) {
			$priority = array_keys($waiting)[0];
			DI::logger()->info('No underassigned priority found, now taking the highest priority.', ['priority' => $priority]);
			return $priority;
		}

		return false;
	}

	/**
	 * Find and claim the next worker process for us
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function findWorkerProcesses()
	{
		$fetch_limit = DI::config()->get('system', 'worker_fetch_limit', 1);

		if (DI::config()->get('system', 'worker_multiple_fetch')) {
			$pids = [];
			foreach (self::getWorkerPIDList() as $pid => $count) {
				if ($count <= $fetch_limit) {
					$pids[] = $pid;
				}
			}
			if (empty($pids)) {
				return;
			}
			$limit = $fetch_limit * count($pids);
		} else {
			$pids  = [getmypid()];
			$limit = $fetch_limit;
		}

		$ids = self::nextProcess($limit);
		$limit -= count($ids);

		// If there is not enough results we check without priority limit
		if ($limit > 0) {
			$stamp     = (float)microtime(true);
			$condition = ["`pid` = 0 AND NOT `done` AND `next_try` < ?", DateTimeFormat::utcNow()];
			$tasks     = DBA::select('workerqueue', ['id', 'command', 'parameter'], $condition, ['limit' => $limit, 'order' => ['priority', 'retrial', 'created']]);
			self::$db_duration += (microtime(true) - $stamp);

			while ($task = DBA::fetch($tasks)) {
				$ids[] = $task['id'];
				// Only continue that loop while we are storing commands that can be processed quickly
				if (!empty($task['command'])) {
					$command = $task['command'];
				} else {
					$command = json_decode($task['parameter'])[0];
				}
				if (!in_array($command, self::FAST_COMMANDS)) {
					break;
				}
			}
			DBA::close($tasks);
		}

		if (empty($ids)) {
			return;
		}

		// Assign the task ids to the workers
		$worker = [];
		foreach (array_unique($ids) as $id) {
			$pid = next($pids);
			if (!$pid) {
				$pid = reset($pids);
			}
			$worker[$pid][] = $id;
		}

		$stamp = (float)microtime(true);
		foreach ($worker as $worker_pid => $worker_ids) {
			DI::logger()->info('Set queue entry', ['pid' => $worker_pid, 'ids' => $worker_ids]);
			DBA::update(
				'workerqueue',
				['executed' => DateTimeFormat::utcNow(), 'pid' => $worker_pid],
				['id'       => $worker_ids, 'done' => false, 'pid' => 0]
			);
		}
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * Returns the next worker process
	 *
	 * @return array worker processes
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function workerProcess(): array
	{
		// There can already be jobs for us in the queue.
		$waiting = self::getWaitingJobForPID();
		if (!empty($waiting)) {
			return $waiting;
		}

		$stamp = (float)microtime(true);
		if (!DI::lock()->acquire(self::LOCK_PROCESS)) {
			return [];
		}
		self::$lock_duration += (microtime(true) - $stamp);

		self::findWorkerProcesses();

		DI::lock()->release(self::LOCK_PROCESS);

		// Prevents "Return value of Friendica\Core\Worker::workerProcess() must be of the type array, bool returned"
		$process = self::getWaitingJobForPID();
		return (is_array($process) ? $process : []);
	}

	/**
	 * Removes a workerqueue entry from the current process
	 *
	 * @param Process $process the process behind the workerqueue
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function unclaimProcess(Process $process)
	{
		$stamp = (float)microtime(true);
		DBA::update('workerqueue', ['executed' => DBA::NULL_DATETIME, 'pid' => 0], ['pid' => $process->pid, 'done' => false]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * Fork a child process
	 *
	 * @param boolean $do_cron
	 * @return void
	 */
	private static function forkProcess(bool $do_cron)
	{
		if (DI::system()->isMinMemoryReached()) {
			DI::logger()->warning('Memory limit reached - quitting');
			return;
		}

		// Children inherit their parent's database connection.
		// To avoid problems we disconnect and connect both parent and child
		DBA::disconnect();
		$pid = pcntl_fork();
		if ($pid == -1) {
			DBA::connect();
			DI::logger()->warning('Could not spawn worker');
			return;
		} elseif ($pid) {
			// The parent process continues here
			DBA::connect();

			Worker\IPC::SetJobState(true, $pid);
			DI::logger()->info('Spawned new worker', ['pid' => $pid]);

			$cycles = 0;
			while (Worker\IPC::JobsExists($pid) && (++$cycles < 100)) {
				usleep(10000);
			}

			DI::logger()->info('Spawned worker is ready', ['pid' => $pid, 'wait_cycles' => $cycles]);
			return;
		}

		// We now are in the new worker
		DBA::connect();

		DI::flushLogger();
		$process = DI::process()->create(getmypid(), basename(__FILE__));

		$cycles = 0;
		while (!Worker\IPC::JobsExists($process->pid) && (++$cycles < 100)) {
			usleep(10000);
		}

		DI::logger()->info('Worker spawned', ['pid' => $process->pid, 'wait_cycles' => $cycles]);

		self::processQueue($do_cron, $process);

		self::unclaimProcess($process);

		Worker\IPC::SetJobState(false, $process->pid);
		DI::process()->delete($process);
		DI::logger()->info('Worker ended', ['pid' => $process->pid]);
		exit();
	}

	/**
	 * Spawns a new worker
	 *
	 * @param bool $do_cron
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function spawnWorker(bool $do_cron = false)
	{
		if (Worker\Daemon::isMode() && DI::config()->get('system', 'worker_fork')) {
			self::forkProcess($do_cron);
		} else {
			DI::system()->run('bin/console.php', ['worker'], ['no_cron' => !$do_cron]);
		}
		if (Worker\Daemon::isMode()) {
			Worker\IPC::SetJobState(false);
		}
	}

	/**
	 * Adds tasks to the worker queue
	 *
	 * @param integer|array $args priority or parameter array, strings are deprecated and are ignored
	 *
	 * next args are passed as $cmd command line
	 * or: Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::DELETION, $drop_id);
	 * or: Worker::add(array('priority' => Worker::PRIORITY_HIGH, 'dont_fork' => true), 'Delivery', $post_id);
	 *
	 * @return int '0' if worker queue entry already existed or there had been an error, otherwise the ID of the worker task
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @note $cmd and string args are surrounded with ''
	 *
	 * @hooks 'proc_run'
	 *    array $arr
	 *
	 */
	public static function add(...$args)
	{
		if (!count($args)) {
			return 0;
		}

		$arr = ['args' => $args, 'run_cmd' => true];

		Hook::callAll('proc_run', $arr);
		if (!$arr['run_cmd'] || !count($args)) {
			return 1;
		}

		$priority = self::PRIORITY_MEDIUM;
		// Don't fork from frontend tasks by default
		$dont_fork      = DI::config()->get('system', 'worker_dont_fork', false) || !DI::mode()->isBackend();
		$created        = DateTimeFormat::utcNow();
		$delayed        = DBA::NULL_DATETIME;
		$force_priority = false;

		$run_parameter = array_shift($args);

		if (is_int($run_parameter)) {
			$priority = $run_parameter;
		} elseif (is_array($run_parameter)) {
			if (isset($run_parameter['delayed'])) {
				$delayed = $run_parameter['delayed'];
			}
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
		} else {
			throw new \InvalidArgumentException('Priority number or task parameter array expected as first argument');
		}

		$command    = array_shift($args);
		$parameters = json_encode($args);
		$queue      = DBA::selectFirst('workerqueue', ['id', 'priority'], ['command' => $command, 'parameter' => $parameters, 'done' => false]);
		$added      = 0;

		if (!is_int($priority) || !in_array($priority, self::PRIORITIES)) {
			DI::logger()->warning('Invalid priority', ['priority' => $priority, 'command' => $command]);
			$priority = self::PRIORITY_MEDIUM;
		}

		// Quit if there was a database error - a precaution for the update process to 3.5.3
		if (DBA::errorNo() != 0) {
			return 0;
		}

		if (empty($queue)) {
			if (!DBA::insert('workerqueue', ['command' => $command, 'parameter' => $parameters, 'created' => $created,
				'priority'                                => $priority, 'next_try' => $delayed])) {
				return 0;
			}
			$added = DBA::lastInsertId();
		} elseif ($force_priority) {
			$ret = DBA::update('workerqueue', ['priority' => $priority], ['command' => $command, 'parameter' => $parameters, 'done' => false, 'pid' => 0]);
			if ($ret && ($priority != $queue['priority'])) {
				$added = $queue['id'];
			}
		}

		// Set the IPC flag to ensure an immediate process execution via daemon
		if (Worker\Daemon::isMode()) {
			Worker\IPC::SetJobState(true);
		}

		Worker\Daemon::checkState();

		// Should we quit and wait for the worker to be called as a cronjob?
		if ($dont_fork || self::systemLimitReached()) {
			return $added;
		}

		// If there is a lock then we don't have to check for too much worker
		if (!DI::lock()->acquire(self::LOCK_WORKER, 0)) {
			return $added;
		}

		// If there are already enough workers running, don't fork another one
		$quit = self::tooMuchWorkers();
		DI::lock()->release(self::LOCK_WORKER);

		if ($quit) {
			return $added;
		}

		// Quit on daemon mode, except the priority is critical (like for db updates)
		if (Worker\Daemon::isMode() && $priority !== self::PRIORITY_CRITICAL) {
			return $added;
		}

		// Now call the worker to execute the jobs that we just added to the queue
		self::spawnWorker();

		return $added;
	}

	public static function countWorkersByCommand(string $command): int
	{
		return DBA::count('workerqueue', ['done' => false, 'pid' => 0, 'command' => $command]);
	}

	/**
	 * Returns the next retrial level for worker jobs.
	 * This function will skip levels when jobs are older.
	 *
	 * @param array $queue Worker queue entry
	 * @param integer $max_level maximum retrial level
	 * @return integer the next retrial level value
	 */
	private static function getNextRetrial(array $queue, int $max_level): int
	{
		$created      = strtotime($queue['created']);
		$retrial_time = time() - $created;

		$new_retrial = $queue['retrial'] + 1;
		$total       = 0;
		for ($retrial = 0; $retrial <= $max_level + 1; ++$retrial) {
			$delay = (($retrial + 3) ** 4) + (rand(1, 30) * ($retrial + 1));
			$total += $delay;
			if (($total < $retrial_time) && ($retrial > $queue['retrial'])) {
				$new_retrial = $retrial;
			}
		}
		DI::logger()->notice('New retrial for task', ['id' => $queue['id'], 'created' => $queue['created'], 'old' => $queue['retrial'], 'new' => $new_retrial]);
		return $new_retrial;
	}

	/**
	 * Get the number of retrials for the current worker task
	 *
	 * @return integer
	 */
	public static function getRetrial(): int
	{
		$queue = DI::appHelper()->getQueue();
		return $queue['retrial'] ?? 0;
	}

	/**
	 * Defers the current worker entry
	 *
	 * @param int $worker_defer_limit Maximum defer limit
	 * @return boolean had the entry been deferred?
	 * @throws \Exception
	 */
	public static function defer(int $worker_defer_limit = 0): bool
	{
		$queue = DI::appHelper()->getQueue();

		if (empty($queue)) {
			return false;
		}

		$id       = $queue['id'];
		$priority = $queue['priority'];

		$max_level = DI::config()->get('system', 'worker_defer_limit');

		if ($worker_defer_limit) {
			$max_level = min($worker_defer_limit, $max_level);
		}

		$new_retrial = self::getNextRetrial($queue, $max_level);

		if ($new_retrial > $max_level) {
			DI::logger()->notice('The task exceeded the maximum retry count', ['id' => $id, 'created' => $queue['created'], 'old_prio' => $queue['priority'], 'old_retrial' => $queue['retrial'], 'max_level' => $max_level, 'retrial' => $new_retrial]);
			return false;
		}

		// Calculate the delay until the next trial
		$delay = (($new_retrial + 2) ** 4) + (rand(1, 30) * ($new_retrial));
		$next  = DateTimeFormat::utc('now + ' . $delay . ' seconds');

		if (($priority < self::PRIORITY_MEDIUM) && ($new_retrial > 3)) {
			$priority = self::PRIORITY_MEDIUM;
		} elseif (($priority < self::PRIORITY_LOW) && ($new_retrial > 6)) {
			$priority = self::PRIORITY_LOW;
		} elseif (($priority < self::PRIORITY_NEGLIGIBLE) && ($new_retrial > 8)) {
			$priority = self::PRIORITY_NEGLIGIBLE;
		}

		DI::logger()->info('Deferred task', ['id' => $id, 'retrial' => $new_retrial, 'created' => $queue['created'], 'next_execution' => $next, 'old_prio' => $queue['priority'], 'new_prio' => $priority]);

		$stamp  = (float)microtime(true);
		$fields = ['retrial' => $new_retrial, 'next_try' => $next, 'executed' => DBA::NULL_DATETIME, 'pid' => 0, 'priority' => $priority];
		DBA::update('workerqueue', $fields, ['id' => $id]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);

		return true;
	}

	/**
	 * Check if the system is inside the defined maintenance window
	 *
	 * @param bool $check_last_execution Whether check last execution
	 * @return boolean
	 */
	public static function isInMaintenanceWindow(bool $check_last_execution = false): bool
	{
		// Calculate the seconds of the start and end of the maintenance window
		$start = strtotime(DI::config()->get('system', 'maintenance_start')) % 86400;
		$end   = strtotime(DI::config()->get('system', 'maintenance_end')) % 86400;

		DI::logger()->info('Maintenance window', ['start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);

		if ($check_last_execution) {
			// Calculate the window duration
			$duration = max($start, $end) - min($start, $end);

			// Quit when the last cron execution had been after the previous window
			$last_cron = DI::keyValue()->get('last_cron_daily');
			if ($last_cron + $duration > time()) {
				DI::logger()->info('The Daily cron had been executed recently', ['last' => date(DateTimeFormat::MYSQL, $last_cron), 'start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);
				return false;
			}
		}

		$current = time() % 86400;

		if ($start < $end) {
			// Execute if we are inside the window
			$execute = ($current >= $start) && ($current <= $end);
		} else {
			// Don't execute if we are outside the window
			$execute = !(($current > $end) && ($current < $start));
		}

		if ($execute) {
			DI::logger()->info('We are inside the maintenance window', ['current' => date('H:i:s', $current), 'start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);
		} else {
			DI::logger()->info('We are outside the maintenance window', ['current' => date('H:i:s', $current), 'start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);
		}

		return $execute;
	}
}
