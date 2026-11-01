<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\DI;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Static access to the event dispatcher for modules created without the DI container.
 *
 * It keeps the DI:: call out of BaseModule, so its subclasses don't inherit a dependency on the DI container.
 * New code injects EventDispatcherInterface instead.
 */
final class LegacyEventDispatcher
{
	public static function get(): EventDispatcherInterface
	{
		return DI::eventDispatcher();
	}
}
