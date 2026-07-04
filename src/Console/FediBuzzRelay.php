<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Protocol\ActivityPub\Firehose;
use Friendica\System\Daemon as SysDaemon;
use RuntimeException;

/**
 * Console command for streaming federation updates from FediBuzz relay
 */
final class FediBuzzRelay extends Console
{
	public function __construct(
		private readonly Mode $mode,
		private readonly IManageConfigValues $config,
		private readonly IManageKeyValuePairs $keyValue,
		private readonly SysDaemon $daemon,
		private readonly Firehose $firehose,
		?array $argv = null,
	) {
		parent::__construct($argv);
	}

	protected function getHelp(): string
	{
		return <<<HELP
fedibuzzrelay - Interact with the FediBuzz relay daemon
Synopsis
	bin/console fedibuzzrelay start [-h|--help|-?] [-v] [-f]
	bin/console fedibuzzrelay stop [-h|--help|-?] [-v]
	bin/console fedibuzzrelay status [-h|--help|-?] [-v]

Description
	Interact with the FediBuzz relay daemon for streaming federation updates

Options
	-h|--help|-?    Show help information
	-v              Show more debug information.
	-f|--foreground Runs the daemon in the foreground

Examples
	bin/console fedibuzzrelay start -f
		Starts the daemon in the foreground

	bin/console fedibuzzrelay status
		Gets the status of the daemon

	bin/console fedibuzzrelay stop
		Stops the daemon
HELP;
	}

	/**
	 * Execute the console command
	 *
	 * @return int
	 */
	protected function doExecute(): int
	{
		if ($this->mode->isInstall()) {
			throw new RuntimeException("Friendica isn't properly installed yet");
		}

		$this->config->reload();

		if (empty($this->config->get('fedibuzzrelay', 'pidfile'))) {
			throw new RuntimeException(
				<<< TXT
					Please set fedibuzzrelay.pidfile in config/local.config.php. For example:

					'fedibuzzrelay' => [
						'pidfile' => '/path/to/fedibuzzrelay.pid',
					],
				TXT,
			);
		}

		$pidfile = $this->config->get('fedibuzzrelay', 'pidfile');

		$daemonMode = $this->getArgument(0);
		$foreground = (bool) ($this->getOption(['f', 'foreground']) ?? false);

		if (empty($daemonMode)) {
			throw new RuntimeException("Please use either 'start', 'stop' or 'status'");
		}

		$this->daemon->init($pidfile);

		if ($daemonMode === 'status') {
			if ($this->daemon->isRunning()) {
				$this->out(sprintf("Daemon process %s is running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
			} else {
				$this->out(sprintf("Daemon process %s isn't running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
			}
			return 0;
		}

		if ($daemonMode === 'stop') {
			if (!$this->daemon->isRunning()) {
				$this->out(sprintf("Daemon process %s isn't running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
				return 0;
			}

			if ($this->daemon->stop()) {
				$this->keyValue->set('fedibuzzrelay_daemon_mode', false);
				$this->out(sprintf("Daemon process %s was killed (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
				return 0;
			}

			return 1;
		}

		if ($this->daemon->isRunning()) {
			$this->out(sprintf("Daemon process %s is already running (%s)", $this->daemon->getPid(), $this->daemon->getPidfile()));
			return 1;
		}

		if ($daemonMode === "start") {
			$this->out("Starting FediBuzz relay daemon");

			$this->daemon->start(fn () => $this->firehose->streamLoop(), $foreground);

			return 0;
		}

		$this->err('Invalid command');
		$this->out($this->getHelp());
		return 1;
	}
}
