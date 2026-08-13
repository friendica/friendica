<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\AppMenuEvent;
use PHPUnit\Framework\TestCase;

class AppMenuEventTest extends TestCase
{
	private function createEvent(): AppMenuEvent
	{
		return new AppMenuEvent([
			'<div class="app-title"><a href="irc">IRC Chatroom</a></div>',
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

		$this->assertSame(AppMenuEvent::NAME, $event->getName());
	}

	public function testGetAppMenuArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(
			['<div class="app-title"><a href="irc">IRC Chatroom</a></div>'],
			$event->getAppMenuArray(),
		);
	}

	public function testSetAppMenuArray(): void
	{
		$event = $this->createEvent();

		$event->setAppMenuArray([
			'<div class="app-title"><a href="tictac">Tic-Tac-Toe</a></div>',
		]);

		$this->assertSame(
			['<div class="app-title"><a href="tictac">Tic-Tac-Toe</a></div>'],
			$event->getAppMenuArray(),
		);
	}
}
