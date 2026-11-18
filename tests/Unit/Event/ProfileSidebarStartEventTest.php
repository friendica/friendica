<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ProfileSidebarStartEvent;
use PHPUnit\Framework\TestCase;

class ProfileSidebarStartEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new ProfileSidebarStartEvent([]);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ProfileSidebarStartEvent([]);

		$this->assertSame(ProfileSidebarStartEvent::NAME, $event->getName());
	}

	public function testGetProfile(): void
	{
		$event = new ProfileSidebarStartEvent(['name' => 'Alice']);

		$this->assertSame(['name' => 'Alice'], $event->getProfileArray());
	}

	public function testSetProfile(): void
	{
		$event = new ProfileSidebarStartEvent([]);

		$event->setProfileArray(['name' => 'Alice']);

		$this->assertSame(['name' => 'Alice'], $event->getProfileArray());
	}
}
