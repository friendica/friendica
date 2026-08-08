<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ProfileTabsEvent;
use PHPUnit\Framework\TestCase;

class ProfileTabsEventTest extends TestCase
{
	private function createEvent(): ProfileTabsEvent
	{
		return new ProfileTabsEvent(
			true,
			'testnick',
			'status',
			[['label' => 'Posts', 'url' => '/profile/testnick/conversations', 'sel' => 'active', 'title' => 'All posts', 'id' => 'status-tab', 'accesskey' => 'm']],
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

		$this->assertSame(ProfileTabsEvent::NAME, $event->getName());
	}

	public function testGetValuesReturnsValues(): void
	{
		$event = $this->createEvent();

		$this->assertTrue($event->isOwner());
		$this->assertSame('testnick', $event->getNickname());
		$this->assertSame('status', $event->getTab());
		$this->assertSame([['label' => 'Posts', 'url' => '/profile/testnick/conversations', 'sel' => 'active', 'title' => 'All posts', 'id' => 'status-tab', 'accesskey' => 'm']], $event->getTabsArray());
	}

	public function testGetSetTabsArray(): void
	{
		$event = $this->createEvent();

		$tabs = [['label' => 'Other', 'url' => '/profile/testnick/other', 'sel' => '', 'title' => 'Other', 'id' => 'other-tab', 'accesskey' => 'o']];

		$event->setTabsArray($tabs);

		$this->assertSame($tabs, $event->getTabsArray());
	}
}
