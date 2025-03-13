<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use FastRoute\RouteCollector;

/**
 * Allow addons to collect routes.
 *
 * @internal
 */
final class CollectRoutesEvent extends Event
{
	public const COLLECT_ROUTES = 'friendica.collect_routes';

	private RouteCollector $routeCollector;

	public function __construct(string $name, RouteCollector $routeCollector)
	{
		parent::__construct($name);

		$this->routeCollector = $routeCollector;
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
