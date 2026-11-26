<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ProfileSidebarEvent;
use PHPUnit\Framework\TestCase;

class ProfileSidebarEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new ProfileSidebarEvent(['uid' => 1], '<p>entry</p>');

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ProfileSidebarEvent(['uid' => 1], '<p>entry</p>');

		$this->assertSame(ProfileSidebarEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new ProfileSidebarEvent(['uid' => 1], '<p>entry</p>');

		$this->assertSame(['uid' => 1], $event->getProfileArray());
		$this->assertSame('<p>entry</p>', $event->getEntry());

		$event->setEntry('<p>modified</p>');

		$this->assertSame('<p>modified</p>', $event->getEntry());
	}
}
