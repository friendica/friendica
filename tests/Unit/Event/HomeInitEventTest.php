<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\HomeInitEvent;
use PHPUnit\Framework\TestCase;

class HomeInitEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new HomeInitEvent();

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new HomeInitEvent();

		$this->assertSame(HomeInitEvent::NAME, $event->getName());
	}
}
