<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\GenerateNamedMapEvent;
use PHPUnit\Framework\TestCase;

class GenerateNamedMapEventTest extends TestCase
{
	private function createEvent(): GenerateNamedMapEvent
	{
		return new GenerateNamedMapEvent('Berlin', 0);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(GenerateNamedMapEvent::NAME, $event->getName());
	}

	public function testGetLocationReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('Berlin', $event->getLocation());
	}

	public function testGetModeReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(0, $event->getMode());
	}

	public function testGetHtmlIsEmptyByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertSame('', $event->getHtml());
	}

	public function testSetHtml(): void
	{
		$event = $this->createEvent();

		$event->setHtml('<iframe>');

		$this->assertSame('<iframe>', $event->getHtml());
	}
}
