<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Event;

/**
 * Base class for all Friendica events.
 *
 * @internal
 */
abstract class AbstractEvent implements NamedEvent
{
	public function __construct(private readonly string $name) {}

	public function getName(): string
	{
		return $this->name;
	}
}
