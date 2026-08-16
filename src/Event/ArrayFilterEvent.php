<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Allow Event listener to modify an array.
 *
 * @internal
 */
final class ArrayFilterEvent extends AbstractEvent
{
	public const EVENT_CREATED = 'friendica.data.event_created';

	public const EVENT_UPDATED = 'friendica.data.event_updated';

	public const NOTIFY = 'friendica.data.notify';

	public function __construct(string $name, private array $array)
	{
		parent::__construct($name);
	}

	public function getArray(): array
	{
		return $this->array;
	}

	public function setArray(array $array): void
	{
		$this->array = $array;
	}
}
