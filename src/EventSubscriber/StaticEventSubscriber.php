<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\EventSubscriber;

/**
 * Define events that should be reacted to.
 *
 * @internal
 */
interface StaticEventSubscriber
{
	/**
	 * Return an array of events to subscribe to.
	 * The key must the event class name.
	 * The value must the method of the implementing class to call.
	 * The method will be called statically with the event class as first parameter.
	 *
	 * Example:
	 *
	 * ```php
	 * return [Event::class => 'onEvent'];
	 * ```
	 *
	 * @return array<class-string, string>
	 */
	public static function getStaticSubscribedEvents(): array;
}
