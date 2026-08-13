<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\AclLookupEndEvent;
use PHPUnit\Framework\TestCase;

class AclLookupEndEventTest extends TestCase
{
	private function createEvent(): AclLookupEndEvent
	{
		return new AclLookupEndEvent(
			5,
			0,
			10,
			[['type' => 'circle', 'name' => 'Friends', 'id' => 1]],
			[['type' => 'contact', 'name' => 'John', 'id' => 2]],
			[['type' => 'contact', 'name' => 'Jane', 'id' => 3]],
			'contact',
			'john',
		);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(AclLookupEndEvent::NAME, $event->getName());
	}

	public function testGettersReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame(5, $event->getTotal());
		$this->assertSame(0, $event->getStart());
		$this->assertSame(10, $event->getCount());
		$this->assertSame([['type' => 'circle', 'name' => 'Friends', 'id' => 1]], $event->getCircles());
		$this->assertSame([['type' => 'contact', 'name' => 'John', 'id' => 2]], $event->getContacts());
		$this->assertSame([['type' => 'contact', 'name' => 'Jane', 'id' => 3]], $event->getItems());
		$this->assertSame('contact', $event->getType());
		$this->assertSame('john', $event->getSearch());
	}

	public function testSetters(): void
	{
		$event = $this->createEvent();

		$event->setTotal(6);
		$event->setStart(1);
		$event->setCount(20);
		$event->setItems([['type' => 'contact', 'name' => 'Joe', 'id' => 4]]);

		$this->assertSame(6, $event->getTotal());
		$this->assertSame(1, $event->getStart());
		$this->assertSame(20, $event->getCount());
		$this->assertSame([['type' => 'contact', 'name' => 'Joe', 'id' => 4]], $event->getItems());
	}
}
