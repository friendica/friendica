<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\LoggedInEvent;
use PHPUnit\Framework\TestCase;

class LoggedInEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new LoggedInEvent(['uid' => 1]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new LoggedInEvent(['uid' => 1]);

		$this->assertSame(LoggedInEvent::NAME, $event->getName());
	}

	public function testGetters(): void
	{
		$event = new LoggedInEvent(['uid' => 1]);

		$this->assertSame(['uid' => 1], $event->getRecordArray());
	}
}
