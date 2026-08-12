<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ProtocolSupportsRevokeFollowEvent;
use PHPUnit\Framework\TestCase;

class ProtocolSupportsRevokeFollowEventTest extends TestCase
{
	private function createEvent(): ProtocolSupportsRevokeFollowEvent
	{
		return new ProtocolSupportsRevokeFollowEvent('activitypub');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(ProtocolSupportsRevokeFollowEvent::NAME, $event->getName());
	}

	public function testGetProtocolReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('activitypub', $event->getProtocol());
	}

	public function testGetResultReturnsNullByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertNull($event->getResult());
	}

	public function testSetResult(): void
	{
		$event = $this->createEvent();

		$event->setResult(true);

		$this->assertTrue($event->getResult());
	}
}
