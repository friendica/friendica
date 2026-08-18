<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\EventUpdatedEvent;
use PHPUnit\Framework\TestCase;

class EventUpdatedEventTest extends TestCase
{
	private function createEvent(): EventUpdatedEvent
	{
		return new EventUpdatedEvent(['id' => 123, 'summary' => 'test']);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(EventUpdatedEvent::NAME, $event->getName());
	}

	public function testGetEventArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['id' => 123, 'summary' => 'test'], $event->getEventArray());
	}
}
