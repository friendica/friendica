<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\NavInfoEvent;
use PHPUnit\Framework\TestCase;

class NavInfoEventTest extends TestCase
{
	private function createEvent(): NavInfoEvent
	{
		return new NavInfoEvent(
			'<span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>',
			['login' => ['login', 'Sign in', 'selected', 'Sign in']],
			'@friendica@friendi.ca',
			['icon' => 'images/user.png', 'name' => 'John', 'link' => 'profile/john'],
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

		$this->assertSame(NavInfoEvent::NAME, $event->getName());
	}

	public function testGettersReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame('<span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>', $event->getBanner());
		$this->assertSame(['login' => ['login', 'Sign in', 'selected', 'Sign in']], $event->getNavArray());
		$this->assertSame('@friendica@friendi.ca', $event->getSitelocation());
		$this->assertSame(['icon' => 'images/user.png', 'name' => 'John', 'link' => 'profile/john'], $event->getUserinfoArray());
	}

	public function testSetters(): void
	{
		$event = $this->createEvent();

		$event->setBanner('<a href="https://friendi.ca">Friendica</a>');
		$event->setNavArray(['logout' => ['logout', 'Sign out', '', 'End this session']]);
		$event->setSitelocation('@friendica@friendi.ca');
		$event->setUserinfoArray(null);

		$this->assertSame('<a href="https://friendi.ca">Friendica</a>', $event->getBanner());
		$this->assertSame(['logout' => ['logout', 'Sign out', '', 'End this session']], $event->getNavArray());
		$this->assertSame('@friendica@friendi.ca', $event->getSitelocation());
		$this->assertNull($event->getUserinfoArray());
	}
}
