<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\NamedEvent;

/**
 * One-way Event to inform listener about something happend.
 *
 * @internal
 */
class Event implements NamedEvent
{
	/**
	 * Friendica is initialized.
	 */
	public const INIT = 'friendica.init';

	public const HOME_INIT = 'friendica.home_init';

	public const LOGGING_OUT = 'friendica.logging_out';

	public function __construct(private readonly string $name) {}

	public function getName(): string
	{
		return $this->name;
	}
}
