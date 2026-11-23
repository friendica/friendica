<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * The worker is about to execute a queue entry.
 * Long running workers use it to drop per-request caches.
 */
final class WorkerJobStartEvent extends AbstractEvent
{
	public const NAME = 'friendica.worker_job_start';

	/** @internal */
	public function __construct()
	{
		parent::__construct(self::NAME);
	}
}
