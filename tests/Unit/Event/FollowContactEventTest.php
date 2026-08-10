<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\FollowContactEvent;
use PHPUnit\Framework\TestCase;

class FollowContactEventTest extends TestCase
{
	private function createEvent(): FollowContactEvent
	{
		return new FollowContactEvent('https://example.com/profile', 42, ['name' => 'original']);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(FollowContactEvent::NAME, $event->getName());
	}

	public function testGetUrlAndGetUidReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame('https://example.com/profile', $event->getUrl());
		$this->assertSame(42, $event->getUid());
	}

	public function testGetSetContactArray(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['name' => 'original'], $event->getContactArray());

		$event->setContactArray(['name' => 'modified']);

		$this->assertSame(['name' => 'modified'], $event->getContactArray());
	}

	public function testIsAbortedReturnsFalseByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertFalse($event->isAborted());
	}

	public function testSetAborted(): void
	{
		$event = $this->createEvent();

		$event->setAborted();

		$this->assertTrue($event->isAborted());
	}
}
