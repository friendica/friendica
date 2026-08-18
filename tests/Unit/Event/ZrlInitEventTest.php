<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\ZrlInitEvent;
use PHPUnit\Framework\TestCase;

class ZrlInitEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new ZrlInitEvent('https://example.com/zrl', 'https://example.com/command');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ZrlInitEvent('https://example.com/zrl', 'https://example.com/command');

		$this->assertSame(ZrlInitEvent::NAME, $event->getName());
	}

	public function testGetters(): void
	{
		$event = new ZrlInitEvent('https://example.com/zrl', 'https://example.com/command');

		$this->assertSame('https://example.com/zrl', $event->getZrlUrl());
		$this->assertSame('https://example.com/command', $event->getUrl());
	}
}
