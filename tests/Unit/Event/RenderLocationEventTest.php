<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\RenderLocationEvent;
use PHPUnit\Framework\TestCase;

class RenderLocationEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new RenderLocationEvent('Berlin', '52.52,13.405');

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new RenderLocationEvent('Berlin', '52.52,13.405');

		$this->assertSame(RenderLocationEvent::NAME, $event->getName());
	}

	public function testGetLocation(): void
	{
		$event = new RenderLocationEvent('Berlin', '52.52,13.405');

		$this->assertSame('Berlin', $event->getLocation());
	}

	public function testGetCoord(): void
	{
		$event = new RenderLocationEvent('Berlin', '52.52,13.405');

		$this->assertSame('52.52,13.405', $event->getCoord());
	}

	public function testGetHtml(): void
	{
		$event = new RenderLocationEvent('Berlin', '52.52,13.405');

		$this->assertSame('', $event->getHtml());
	}

	public function testSetHtml(): void
	{
		$event = new RenderLocationEvent('Berlin', '52.52,13.405');

		$event->setHtml('<span>Berlin</span>');

		$this->assertSame('<span>Berlin</span>', $event->getHtml());
	}
}
