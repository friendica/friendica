<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\EmailGetMessageEndEvent;
use PHPUnit\Framework\TestCase;

class EmailGetMessageEndEventTest extends TestCase
{
	private function createEvent(): EmailGetMessageEndEvent
	{
		return new EmailGetMessageEndEvent(['body' => 'Body']);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(EmailGetMessageEndEvent::NAME, $event->getName());
	}

	public function testGetItemArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['body' => 'Body'], $event->getItemArray());
	}

	public function testGetItemArrayIsEmptyByDefault(): void
	{
		$event = new EmailGetMessageEndEvent();

		$this->assertSame([], $event->getItemArray());
	}

	public function testSetItemArray(): void
	{
		$event = $this->createEvent();

		$event->setItemArray(['body' => 'New Body']);

		$this->assertSame(['body' => 'New Body'], $event->getItemArray());
	}
}
