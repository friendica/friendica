<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;

/**
 * Modified Event Dispatcher.
 */
final class EventDispatcher extends SymfonyEventDispatcher
{
	/**
	 * Add support for named events.
	 *
	 * @template T of object
	 * @param T $event
	 *
	 * @return T The passed $event MUST be returned
	 */
	public function dispatch(object $event, ?string $eventName = null): object
	{
		if ($eventName === null && $event instanceof NamedEvent) {
			$eventName = $event->getName();
		}

		return parent::dispatch($event, $eventName);
	}
}
