<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Addon;

use Friendica\Addon\Event\AddonStartEvent;

/**
 * Interface an Addon has to implement.
 */
interface AddonBootstrap
{
	/**
	 * Returns an array with the FQCN of required services.
	 *
	 * Example:
	 *
	 * ```php
	 * return [LoggerInterface::class];
	 * ```
	 */
	public function getRequiredDependencies(): array;

	/**
	 * Return an array of events to subscribe to.
	 *
	 * The keys MUST be the event name.
	 * The values MUST be the method of the implementing class to call.
	 *
	 * Example:
	 *
	 * ```php
	 * return [Event::NAME => 'onEvent'];
	 * ```
	 *
	 * @return array<string, string>
	 */
	public function getSubscribedEvents(): array;

	/**
	 * Init the addon with the required dependencies.
	 */
	public function initAddon(AddonStartEvent $event): void;
}
