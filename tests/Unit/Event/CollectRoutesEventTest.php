<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use FastRoute\RouteCollector;
use Friendica\Event\CollectRoutesEvent;
use Friendica\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class CollectRoutesEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new CollectRoutesEvent('test', $this->createStub(RouteCollector::class));

		$this->assertInstanceOf(NamedEvent::class, $event);
	}

	public static function getPublicConstants(): array
	{
		return [
			[CollectRoutesEvent::COLLECT_ROUTES, 'friendica.collect_routes'],
		];
	}

	/**
	 * @dataProvider getPublicConstants
	 */
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new CollectRoutesEvent('test', $this->createStub(RouteCollector::class));

		$this->assertSame('test', $event->getName());
	}

	public function testGetRouteCollectorReturnsCorrectString(): void
	{
		$routeCollector = $this->createStub(RouteCollector::class);

		$event = new CollectRoutesEvent('test', $routeCollector);

		$this->assertSame($routeCollector, $event->getRouteCollector());
	}

	public function testSetRouteCollector(): void
	{
		$event = new CollectRoutesEvent('test', $this->createStub(RouteCollector::class));

		$routeCollector = $this->createStub(RouteCollector::class);

		$event->setRouteCollector($routeCollector);

		$this->assertSame($routeCollector, $event->getRouteCollector());
	}
}
