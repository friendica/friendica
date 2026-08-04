<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Event;

use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;

/**
 * @internal
 */
final class EventDispatcher extends SymfonyEventDispatcher
{
	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * If $eventName is null and $event implements NamedEvent, the event name
	 * will be extracted from the event itself.
	 */
	public function dispatch(object $event, ?string $eventName = null): object
	{
		if ($eventName === null && $event instanceof NamedEvent) {
			$eventName = $event->getName();
		}

		return parent::dispatch($event, $eventName);
	}
}
