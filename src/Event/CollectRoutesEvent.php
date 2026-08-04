<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use FastRoute\RouteCollector;
use Friendica\Core\Event\AbstractEvent;

/**
 * Allow addons to collect routes.
 */
final class CollectRoutesEvent extends AbstractEvent
{
	public const NAME = 'friendica.collect_routes';

	public function __construct(private RouteCollector $routeCollector)
	{
		parent::__construct(self::NAME);
	}

	public function getRouteCollector(): RouteCollector
	{
		return $this->routeCollector;
	}

	public function setRouteCollector(RouteCollector $routeCollector): void
	{
		$this->routeCollector = $routeCollector;
	}
}
