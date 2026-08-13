<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Core\Worker;
use Friendica\Event\AddWorkerTaskEvent;
use PHPUnit\Framework\TestCase;

class AddWorkerTaskEventTest extends TestCase
{
	private function createEvent(): AddWorkerTaskEvent
	{
		return new AddWorkerTaskEvent(
			[Worker::PRIORITY_MEDIUM, 'Notifier', 123],
			true,
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

		$this->assertSame(AddWorkerTaskEvent::NAME, $event->getName());
	}

	public function testGettersReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame([Worker::PRIORITY_MEDIUM, 'Notifier', 123], $event->getArgsArray());
		$this->assertTrue($event->isRunCmd());
	}

	public function testSetRunCmd(): void
	{
		$event = $this->createEvent();

		$event->setRunCmd(false);

		$this->assertFalse($event->isRunCmd());
	}
}
