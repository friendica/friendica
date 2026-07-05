<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Addon\Event;

use Psr\Container\ContainerInterface;

/**
 * Start an addon.
 */
final class AddonStartEvent
{
	private ContainerInterface $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function getContainer(): ContainerInterface
	{
		return $this->container;
	}
}
