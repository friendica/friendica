<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * Event is emitted once the home page is visited.
 *
 * @since 3.7
 */
final class HomeInitEvent extends Event
{
	public const NAME = 'friendica.home_init';

	public function __construct()
	{
		parent::__construct(self::NAME);
	}
}
