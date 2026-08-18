<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\AvatarLookupEvent;
use PHPUnit\Framework\TestCase;

class AvatarLookupEventTest extends TestCase
{
	private function createEvent(): AvatarLookupEvent
	{
		return new AvatarLookupEvent(300, 'contact@example.com');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(AvatarLookupEvent::NAME, $event->getName());
	}

	public function testGetSizeAndGetEmailReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame(300, $event->getSize());
		$this->assertSame('contact@example.com', $event->getEmail());
	}

	public function testGetSetUrl(): void
	{
		$event = $this->createEvent();

		$this->assertSame('', $event->getUrl());

		$event->setUrl('https://example.com/avatar');

		$this->assertSame('https://example.com/avatar', $event->getUrl());
	}

	public function testIsSuccessReturnsFalseByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertFalse($event->isSuccess());
	}

	public function testSetSuccess(): void
	{
		$event = $this->createEvent();

		$event->setSuccess(true);

		$this->assertTrue($event->isSuccess());
	}
}
