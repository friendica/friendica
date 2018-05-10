<?php
/**
 * @file mod/worker.php
 * @brief Module for running the worker as frontend process
 */

use Friendica\Core\Config;
use Friendica\Core\Worker;

function worker_init(){

	if (!Config::get("system", "frontend_worker")) {
		return;
	}

	// We don't need the following lines if we can execute background jobs.
	// So we just wake up the worker if it sleeps.
	if (function_exists("proc_open")) {
		Worker::executeIfIdle();
		return;
	}

	Worker::clearProcesses();

	$workers = q("SELECT COUNT(*) AS `processes` FROM `process` WHERE `command` = 'worker.php'");

	if ($workers[0]["processes"] > Config::get("system", "worker_queues", 4)) {
		return;
	}

	Worker::startProcess();

	logger("Front end worker started: ".getmypid());

	Worker::callWorker();

	if ($r = Worker::workerProcess()) {

		// On most configurations this parameter wouldn't have any effect.
		// But since it doesn't destroy anything, we just try to get more execution time in any way.
		set_time_limit(0);

		if (poller_claim_process($r[0])) {
			Worker::execute($r[0]);
		}
	}

	Worker::callWorker();

	Worker::unclaimProcess();

	Worker::endProcess();

	logger("Front end worker ended: ".getmypid());

	killme();
}
