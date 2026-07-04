<?php

// Copyright (C) 2010-2025, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\System;

use Friendica\Database\Database;
use Psr\Log\LoggerInterface;

/**
 * class for direct interacting with the daemon commands
 */
class Daemon
{
	private ?string $pidfile = null;
	private ?int $pid        = null;

	/**
	 * The PID of the current daemon (null if either not set or not found)
	 *
	 * @return int|null
	 */
	public function getPid(): ?int
	{
		return $this->pid;
	}

	/**
	 * The path to the PID file (null if not set)
	 *
	 * @return string|null
	 */
	public function getPidfile(): ?string
	{
		return $this->pidfile;
	}

	public function __construct(private readonly LoggerInterface $logger, private readonly Database $dba) {}

	/**
	 * Initialize the current daemon class with a given PID file
	 *
	 * @param string|null $pidfile the path to the PID file - using a given path if not directly set here
	 *
	 * @return void
	 */
	public function init(?string $pidfile = null): void
	{
		if (!empty($pidfile)) {
			$this->pid     = null;
			$this->pidfile = $pidfile;
		}

		if (!empty($this->pid)) {
			return;
		}

		if (is_readable($this->pidfile)) {
			$this->pid = intval(file_get_contents($this->pidfile));
		}
	}

	/**
	 * Starts the daemon
	 *
	 * @param callable $daemonLogic the business logic of the daemon
	 * @param bool     $foreground  true, if started in foreground, otherwise spawned in the background
	 *
	 * @return bool true, if successfully started, otherwise false
	 */
	public function start(callable $daemonLogic, bool $foreground = false): bool
	{
		$this->init();

		if (!empty($this->pid)) {
			$this->logger->notice('process is already running', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
			return false;
		}

		$this->logger->notice('starting daemon', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);

		if (!$foreground) {
			$this->dba->disconnect();

			// fork a daemon process
			$this->pid = pcntl_fork();
			if ($this->pid < 0) {
				$this->logger->warning('Could not fork daemon');
				return false;
			} elseif ($this->pid) {
				// The parent process continues here
				if (!file_put_contents($this->pidfile, $this->pid)) {
					$this->logger->warning('Could not store pid file', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
					posix_kill($this->pid, SIGTERM);
					return false;
				}
				$this->logger->notice('Child process started', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
				return true;
			}

			// We now are in the child process
			register_shutdown_function(function (): void {
				posix_kill(posix_getpid(), SIGTERM);
				posix_kill(posix_getpid(), SIGHUP);
			});

			// Make the child the main process, detach it from the terminal
			if (posix_setsid() < 0) {
				return true;
			}

			// Closing all existing connections with the outside
			fclose(STDIN);

			// And now connect the database again
			$this->dba->connect();
		}

		// Just to be sure that this script really runs endlessly
		set_time_limit(0);

		$daemonLogic();

		return true;
	}

	/**
	 * Checks, if the current daemon is running
	 *
	 * @return bool true, if the daemon is running, otherwise false (f.e no PID found, no PID file found, PID is not bound to a running process))
	 */
	public function isRunning(): bool
	{
		$this->init();

		if (empty($this->pid)) {
			$this->logger->notice("Pid wasn't found");

			if (is_readable($this->pidfile)) {
				unlink($this->pidfile);
				$this->logger->notice("Pidfile removed", ['pidfile' => $this->pidfile]);
			}
			return false;
		}

		if (posix_kill($this->pid, 0)) {
			$this->logger->notice("daemon process is running");
			return true;
		} else {
			unlink($this->pidfile);
			$this->logger->notice("daemon process isn't running");
			return false;
		}
	}

	/**
	 * Stops the daemon, if running
	 *
	 * @return bool true, if the daemon was successfully stopped or is already stopped, otherwise false
	 */
	public function stop(): bool
	{
		$this->init();

		if (empty($this->pid)) {
			$this->logger->notice("Pidfile wasn't found", ['pidfile' => $this->pidfile]);
			return true;
		}

		if (!posix_kill($this->pid, SIGTERM)) {
			$this->logger->warning("Cannot kill the given PID", ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
			return false;
		}

		if (!unlink($this->pidfile)) {
			$this->logger->warning("Cannot delete the given PID file", ['pid' => $this->pid, 'pidfile' => $this->pidfile]);
			return false;
		}

		$this->logger->notice('daemon process was killed', ['pid' => $this->pid, 'pidfile' => $this->pidfile]);

		return true;
	}

	/**
	 * Sets the current daemon to sleep and checks the status afterward
	 *
	 * @param int $duration the duration of time for sleeping (in milliseconds)
	 *
	 * @return void
	 */
	public function sleep(int $duration)
	{
		usleep($duration);

		$this->pid = pcntl_waitpid(-1, $status, WNOHANG);
		if ($this->pid > 0) {
			$this->logger->info('Children quit via pcntl_waitpid', ['pid' => $this->pid, 'status' => $status]);
		}
	}
}
