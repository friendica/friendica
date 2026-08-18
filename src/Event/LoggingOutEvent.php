<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when a user is logging out.
 */
final class LoggingOutEvent extends AbstractEvent
{
	public const NAME = 'friendica.logging_out';

	/** @internal */
	public function __construct()
	{
		parent::__construct(self::NAME);
	}
}
