<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Util;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a dispatcher for events.
 */
final class FakeEventDispatcher implements EventDispatcherInterface
{
	/**
	 * Provide all relevant listeners with an event to process.
	 *
	 * @template T of object
	 * @param T $event
	 *
	 * @return T The passed $event MUST be returned
	 */
	public function dispatch(object $event): object
	{
		return $event;
	}
}
