<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\NotifierEndEvent;
use PHPUnit\Framework\TestCase;

class NotifierEndEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new NotifierEndEvent(['id' => 1]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new NotifierEndEvent(['id' => 1]);

		$this->assertSame(NotifierEndEvent::NAME, $event->getName());
	}

	public function testGetter(): void
	{
		$event = new NotifierEndEvent(['id' => 1, 'uri' => 'https://example.com']);

		$this->assertSame(['id' => 1, 'uri' => 'https://example.com'], $event->getItemArray());
	}
}
