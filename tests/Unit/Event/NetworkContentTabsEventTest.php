<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\NetworkContentTabsEvent;
use PHPUnit\Framework\TestCase;

class NetworkContentTabsEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new NetworkContentTabsEvent([]);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new NetworkContentTabsEvent([]);

		$this->assertSame(NetworkContentTabsEvent::NAME, $event->getName());
	}

	public function testGetTabs(): void
	{
		$event = new NetworkContentTabsEvent([['code' => 'all', 'name' => 'All']]);

		$this->assertSame([['code' => 'all', 'name' => 'All']], $event->getTabs());
	}

	public function testSetTabs(): void
	{
		$event = new NetworkContentTabsEvent([]);

		$event->setTabs([['code' => 'all', 'name' => 'All']]);

		$this->assertSame([['code' => 'all', 'name' => 'All']], $event->getTabs());
	}
}
