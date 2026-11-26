<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when an event has been updated, to allow addons to react to the update.
 */
final class EventUpdatedEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.event_updated';

	/**
	 * @internal
	 */
	public function __construct(private readonly array $event)
	{
		parent::__construct(self::NAME);
	}

	public function getEventArray(): array
	{
		return $this->event;
	}
}
