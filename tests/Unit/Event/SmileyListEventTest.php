<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\SmileyListEvent;
use PHPUnit\Framework\TestCase;

class SmileyListEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new SmileyListEvent([], []);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new SmileyListEvent([], []);

		$this->assertSame(SmileyListEvent::NAME, $event->getName());
	}

	public function testGetTexts(): void
	{
		$event = new SmileyListEvent(['&lt;3'], ['<img src="heart.gif" />']);

		$this->assertSame(['&lt;3'], $event->getTexts());
	}

	public function testGetIcons(): void
	{
		$event = new SmileyListEvent(['&lt;3'], ['<img src="heart.gif" />']);

		$this->assertSame(['<img src="heart.gif" />'], $event->getIcons());
	}

	public function testSetTexts(): void
	{
		$event = new SmileyListEvent([], []);

		$event->setTexts([':-)']);

		$this->assertSame([':-)'], $event->getTexts());
	}

	public function testSetIcons(): void
	{
		$event = new SmileyListEvent([], []);

		$event->setIcons(['<img src="smile.gif" />']);

		$this->assertSame(['<img src="smile.gif" />'], $event->getIcons());
	}
}
