<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before a task is added to the worker queue.
 *
 * Can be used by addons to prevent the task from being executed.
 */
final class AddWorkerTaskEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.add_worker_task';

	/**
	 * @internal
	 *
	 * @param array $args   The worker task parameters
	 * @param bool  $runCmd Whether the worker task should be executed
	 */
	public function __construct(
		private readonly array $args,
		private bool $runCmd = true,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array The worker task parameters
	 */
	public function getArgsArray(): array
	{
		return $this->args;
	}

	public function isRunCmd(): bool
	{
		return $this->runCmd;
	}

	public function setRunCmd(bool $runCmd): void
	{
		$this->runCmd = $runCmd;
	}
}
