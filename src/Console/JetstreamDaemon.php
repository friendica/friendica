<?php

// Copyright (C) 2010-2025, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\App\Mode;
use Friendica\Core\Addon;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Protocol\ATProtocol\Jetstream;
use Friendica\System\Daemon as SysDaemon;
use RuntimeException;

/**
 * Console command for interacting with the daemon
 */
final class JetstreamDaemon extends Console
{
	private Mode $mode;
	private IManageConfigValues $config;
	private IManageKeyValuePairs $keyValue;
	private SysDaemon $daemon;
	private Jetstream $jetstream;

	/**
	 * @param Mode                 $mode
	 * @param IManageConfigValues  $config
	 * @param IManageKeyValuePairs $keyValue
	 * @param SysDaemon            $daemon
	 * @param Jetstream            $jetstream
	 * @param array|null           $argv
	 */
	public function __construct(Mode $mode, IManageConfigValues $config, IManageKeyValuePairs $keyValue, SysDaemon $daemon, Jetstream $jetstream, array $argv = null)
	{
		parent::__construct($argv);

		$this->mode      = $mode;
		$this->config    = $config;
		$this->keyValue  = $keyValue;
		$this->jetstream = $jetstream;
		$this->daemon    = $daemon;
	}

	protected function getHelp(): string
	{
		return <<<HELP
jetstream - Interact with the Jetstream daemon
Synopsis
	bin/console jetstream start [-h|--help|-?] [-v] [-f]
	bin/console jetstream stop [-h|--help|-?] [-v]
	bin/console jetstream status [-h|--help|-?] [-v]

Description
    Interact with the Jetstream daemon

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -f|--foreground         Runs the daemon in the foreground

Examples
	bin/console jetstream start -f
		Starts the daemon in the foreground

	bin/console jetstream status
		Gets the status of the daemon
HELP;
	}

	protected function doExecute()
	{
		if ($this->mode->isInstall()) {
			throw new RuntimeException("Friendica isn't properly installed yet");
		}

		$this->config->reload();

		if (empty($this->config->get('jetstream', 'pidfile'))) {
			throw new RuntimeException(
				<<< TXT
					Please set jetstream.pidfile in config/local.config.php. For example:

						'jetstream' => [
							'pidfile' => '/path/to/jetstream.pid',
						],
					TXT
			);
		}

		Addon::loadAddons();
		Hook::loadHooks();

		if (!Addon::isEnabled('bluesky')) {
			throw new RuntimeException("Bluesky has to be enabled.\n");
		}

		$pidfile = $this->config->get('jetstream', 'pidfile');

		$daemonMode = $this->getArgument(0);
		$foreground = $this->getOption(['f', 'foreground']) ?? false;

		if (empty($daemonMode)) {
			throw new RuntimeException("Please use either 'start', 'stop' or 'status'");
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
			$this->out("Starting Jetstream daemon");

			$this->daemon->start(function () {
				$this->jetstream->listen();
			}, $foreground);

			return 0;
		}

		$this->err('Invalid command');
		$this->out($this->getHelp());
		return 1;
	}
}
