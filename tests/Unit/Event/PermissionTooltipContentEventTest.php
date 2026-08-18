<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\PermissionTooltipContentEvent;
use PHPUnit\Framework\TestCase;

class PermissionTooltipContentEventTest extends TestCase
{
	private function createEvent(): PermissionTooltipContentEvent
	{
		return new PermissionTooltipContentEvent([
			'uid'     => 42,
			'private' => 1,
			'uri-id'  => 1234,
		]);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(PermissionTooltipContentEvent::NAME, $event->getName());
	}

	public function testGetModelReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame([
			'uid'     => 42,
			'private' => 1,
			'uri-id'  => 1234,
		], $event->getModelArray());
	}

	public function testSetModel(): void
	{
		$event = $this->createEvent();

		$event->setModelArray(['uid' => 7, 'private' => 0, 'uri-id' => 99]);

		$this->assertSame(['uid' => 7, 'private' => 0, 'uri-id' => 99], $event->getModelArray());
	}
}
