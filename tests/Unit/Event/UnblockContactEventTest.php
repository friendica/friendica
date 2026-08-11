<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\UnblockContactEvent;
use PHPUnit\Framework\TestCase;

class UnblockContactEventTest extends TestCase
{
	private function createEvent(): UnblockContactEvent
	{
		return new UnblockContactEvent(['url' => 'https://example.com', 'name' => 'original'], 42);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(UnblockContactEvent::NAME, $event->getName());
	}

	public function testGetUidReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(42, $event->getUid());
	}

	public function testGetContactArray(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['url' => 'https://example.com', 'name' => 'original'], $event->getContactArray());
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
