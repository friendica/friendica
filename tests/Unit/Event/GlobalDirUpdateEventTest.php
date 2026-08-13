<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\GlobalDirUpdateEvent;
use PHPUnit\Framework\TestCase;

class GlobalDirUpdateEventTest extends TestCase
{
	private function createEvent(): GlobalDirUpdateEvent
	{
		return new GlobalDirUpdateEvent('https://example.org/profile');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(GlobalDirUpdateEvent::NAME, $event->getName());
	}

	public function testGetUrlReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('https://example.org/profile', $event->getUrl());
	}

	public function testSetUrl(): void
	{
		$event = $this->createEvent();

		$event->setUrl('');

		$this->assertSame('', $event->getUrl());
	}
}
