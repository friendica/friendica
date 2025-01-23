<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Asika\SimpleConsole\Console;
use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Core\System;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\System\Daemon as SysDaemon;
use Friendica\Util\BasePath;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Console command for interacting with the daemon
 */
final class Daemon extends Console
{
	private Mode $mode;
	private IManageConfigValues $config;
	private IManageKeyValuePairs $keyValue;
	private BasePath $basePath;
	private System $system;
	private LoggerInterface $logger;
	private Database $dba;
	private SysDaemon $daemon;

	/**
	 * @param Mode                 $mode
	 * @param IManageConfigValues  $config
	 * @param IManageKeyValuePairs $keyValue
	 * @param BasePath             $basePath
	 * @param System               $system
	 * @param LoggerInterface      $logger
	 * @param Database             $dba
	 * @param SysDaemon            $daemon
	 * @param array|null           $argv
	 */
	public function __construct(Mode $mode, IManageConfigValues $config, IManageKeyValuePairs $keyValue, BasePath $basePath, System $system, LoggerInterface $logger, Database $dba, SysDaemon $daemon, array $argv = null)
	{
		parent::__construct($argv);

		$this->mode     = $mode;
		$this->config   = $config;
		$this->keyValue = $keyValue;
		$this->basePath = $basePath;
		$this->system   = $system;
		$this->logger   = $logger;
		$this->dba      = $dba;
		$this->daemon   = $daemon;
	}

	protected function getHelp(): string
	{
		return <<<HELP
Daemon - Interact with the Friendica daemon
Synopsis
	bin/console daemon start [-h|--help|-?] [-v] [-f]
	bin/console daemon stop [-h|--help|-?] [-v]
	bin/console daemon status [-h|--help|-?] [-v]

Description
    Interact with the Friendica daemon

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -f|--foreground         Runs the daemon in the foreground

Examples
	bin/console daemon start -f
		Starts the daemon in the foreground

	bin/console daemon status
		Gets the status of the daemon
HELP;
	}

	protected function doExecute()
	{
		if ($this->mode->isInstall()) {
			throw new RuntimeException("Friendica isn't properly installed yet");
		}

		$this->mode->setExecutor(Mode::DAEMON);

		$this->config->reload();

		if (empty($this->config->get('system', 'pidfile'))) {
			throw new RuntimeException(
				<<< TXT
					Please set system.pidfile in config/local.config.php. For example:

						'system' => [
							'pidfile' => '/path/to/daemon.pid',
						],
					TXT
			);
		}

		$pidfile = $this->config->get('system', 'pidfile');

		$daemonMode = $this->getArgument(0);
		$foreground = $this->getOption(['f', 'foreground']) ?? false;

		if (empty($daemonMode)) {
			throw new CommandArgsException("Please use either 'start', 'stop' or 'status'");
		}

		$this->daemon->init($pidfile);

		if ($daemonMode == 'status') {
			if ($this->daemon->isRunning()) {
				$this->out(sprintf("Daemon process %s is running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
			} else {
				$this->out(sprintf("Daemon process %s isn't running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
			}
			return 0;
		}

		if ($daemonMode == 'stop') {
			if (!$this->daemon->isRunning()) {
				$this->out(sprintf("Daemon process %s isn't running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
				return 0;
			}

			if ($this->daemon->stop()) {
				$this->keyValue->set('worker_daemon_mode', false);
				$this->out(sprintf("Daemon process %s was killed (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
				return 0;
			}

			return 1;
		}

		if ($this->daemon->isRunning()) {
			$this->out(sprintf("Daemon process %s is already running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
			return 1;
		}

		if ($daemonMode == "start") {
			$this->out("Starting Friendica daemon");

			$this->daemon->start(function () {
				$wait_interval = intval($this->config->get('system', 'cron_interval', 5)) * 60;

				$do_cron   = true;
				$last_cron = 0;

				$path = $this->basePath->getPath();

				// Now running as a daemon.
				while (true) {
					// Check the database structure and possibly fixes it
					Update::check($path, true);

					if (!$do_cron && ($last_cron + $wait_interval) < time()) {
						$this->logger->info('Forcing cron worker call.', ['pid' => $this->daemon->getPid()]);
						$do_cron = true;
					}

					if ($do_cron || (!$this->system->isMaxLoadReached() && Worker::entriesExists() && Worker::isReady())) {
						Worker::spawnWorker($do_cron);
					} else {
						$this->logger->info('Cool down for 5 seconds', ['pid' => $this->daemon->getPid()]);
						sleep(5);
					}

					if ($do_cron) {
						// We force a reconnect of the database connection.
						// This is done to ensure that the connection don't get lost over time.
						$this->dba->reconnect();

						$last_cron = time();
					}

					$start = time();
					$this->logger->info('Sleeping', ['pid' => $this->daemon->getPid(), 'until' => gmdate(DateTimeFormat::MYSQL, $start + $wait_interval)]);

					do {
						$seconds = (time() - $start);

						// logarithmic wait time calculation.
						// Background: After jobs had been started, they often fork many workers.
						// To not waste too much time, the sleep period increases.
						$arg   = (($seconds + 1) / ($wait_interval / 9)) + 1;
						$sleep = min(1000000, round(log10($arg) * 1000000, 0));

						$this->daemon->sleep((int)$sleep);

						$timeout = ($seconds >= $wait_interval);
					} while (!$timeout && !Worker\IPC::JobsExists());

					if ($timeout) {
						$do_cron = true;
						$this->logger->info('Woke up after $wait_interval seconds.', ['pid' => $this->daemon->getPid(), 'sleep' => $wait_interval]);
					} else {
						$do_cron = false;
						$this->logger->info('Worker jobs are calling to be forked.', ['pid' => $this->daemon->getPid()]);
					}
				}
			}, $foreground);

			return 0;
		}

		$this->err('Invalid command');
		$this->out($this->getHelp());
		return 1;
	}
}
