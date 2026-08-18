<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\NetworkContentStartEvent;
use PHPUnit\Framework\TestCase;

class NetworkContentStartEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new NetworkContentStartEvent('foo');

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new NetworkContentStartEvent('foo');

		$this->assertSame(NetworkContentStartEvent::NAME, $event->getName());
	}

	public function testGetQuery(): void
	{
		$event = new NetworkContentStartEvent('q=/network');

		$this->assertSame('q=/network', $event->getQuery());
	}
}
