<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\MapGetCoordinatesEvent;
use PHPUnit\Framework\TestCase;

class MapGetCoordinatesEventTest extends TestCase
{
	private function createEvent(): MapGetCoordinatesEvent
	{
		return new MapGetCoordinatesEvent('Berlin');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(MapGetCoordinatesEvent::NAME, $event->getName());
	}

	public function testGetLocationReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('Berlin', $event->getLocation());
	}

	public function testGetLatitudeIsNullByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertNull($event->getLatitude());
	}

	public function testGetLongitudeIsNullByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertNull($event->getLongitude());
	}

	public function testSetLatitude(): void
	{
		$event = $this->createEvent();

		$event->setLatitude('52.5200');

		$this->assertSame('52.5200', $event->getLatitude());
	}

	public function testSetLongitude(): void
	{
		$event = $this->createEvent();

		$event->setLongitude('13.4050');

		$this->assertSame('13.4050', $event->getLongitude());
	}

	public function testSetLatitudeToNull(): void
	{
		$event = $this->createEvent();
		$event->setLatitude('52.5200');

		$event->setLatitude(null);

		$this->assertNull($event->getLatitude());
	}

	public function testSetLongitudeToNull(): void
	{
		$event = $this->createEvent();
		$event->setLongitude('13.4050');

		$event->setLongitude(null);

		$this->assertNull($event->getLongitude());
	}
}
