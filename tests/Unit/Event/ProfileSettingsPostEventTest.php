<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ProfileSettingsPostEvent;
use PHPUnit\Framework\TestCase;

class ProfileSettingsPostEventTest extends TestCase
{
	private function createEvent(): ProfileSettingsPostEvent
	{
		return new ProfileSettingsPostEvent(['name' => 'original']);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(ProfileSettingsPostEvent::NAME, $event->getName());
	}

	public function testGetSetRequestArray(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['name' => 'original'], $event->getRequestArray());

		$event->setRequestArray(['name' => 'modified']);

		$this->assertSame(['name' => 'modified'], $event->getRequestArray());
	}
}
