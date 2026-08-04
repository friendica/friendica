<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\InitEvent;
use Friendica\Core\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class InitEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new InitEvent();

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new InitEvent();

		$this->assertSame('friendica.init', $event->getName());
	}
}
