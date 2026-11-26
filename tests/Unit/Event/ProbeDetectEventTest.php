<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ProbeDetectEvent;
use PHPUnit\Framework\TestCase;

class ProbeDetectEventTest extends TestCase
{
	private function createEvent(): ProbeDetectEvent
	{
		return new ProbeDetectEvent('https://example.com/profile', 'activitypub', 42);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(ProbeDetectEvent::NAME, $event->getName());
	}

	public function testGetUriNetworkAndUidReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame('https://example.com/profile', $event->getUri());
		$this->assertSame('activitypub', $event->getNetwork());
		$this->assertSame(42, $event->getUid());
	}

	public function testGetResultReturnsNullByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertNull($event->getResult());
	}

	public function testSetResult(): void
	{
		$event = $this->createEvent();

		$event->setResult(['name' => 'contact']);

		$this->assertSame(['name' => 'contact'], $event->getResult());
	}

	public function testSetResultFalse(): void
	{
		$event = $this->createEvent();

		$event->setResult(false);

		$this->assertFalse($event->getResult());
	}
}
