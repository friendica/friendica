<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\App\Mode;
use Friendica\Core\Update;
use Friendica\Core\Worker as CoreWorker;
use Friendica\Core\Worker\Repository\Process as ProcessRepository;
use Friendica\Util\BasePath;

/**
 * Console command for starting worker
 */
final class Worker extends Console
{
	/**
	 * @param Mode              $mode
	 * @param BasePath          $basePath
	 * @param ProcessRepository $processRepo
	 * @param array|null        $argv
	 */
	public function __construct(
		private readonly Mode $mode,
		private readonly BasePath $basePath,
		private readonly ProcessRepository $processRepo,
		?array $argv = null,
	) {
		parent::__construct($argv);
	}

	protected function getHelp(): string
	{
		return <<<HELP
Worker - Start a worker
Synopsis
	bin/console worker [-h|--help|-?] [-v] [-n|--no_cron] [-s|--spawn]

Description
    Start a worker process

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -n|--no_cron            Don't executes the Cronjob
    -s|--spawn              Spawn an additional worker

Examples
	bin/console worker -n
		Starts the worker without executing other recurring tasks

	bin/console worker -s
		Starts the worker and immediately spawn another worker process
HELP;
	}

	protected function doExecute()
	{
		$this->mode->setExecutor(Mode::WORKER);

		// Check the database structure and possibly fixes it
		Update::check($this->basePath->getPath(), true);

		// Quit when in maintenance
		if (!$this->mode->has(Mode::MAINTENANCEDISABLED)) {
			return;
		}

		$spawn = $this->getOption(['s', 'spawn'], false);

		if ($spawn) {
			CoreWorker::spawnWorker();
			exit();
		}

		$run_cron = !$this->getOption(['n', 'no_cron'], false);

		$process = $this->processRepo->create(getmypid(), 'worker.php');

		CoreWorker::processQueue($run_cron, $process);
		CoreWorker::unclaimProcess($process);

		$this->processRepo->delete($process);

		return;
	}
}
