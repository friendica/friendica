<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ProfileSettingsFormEvent;
use PHPUnit\Framework\TestCase;

class ProfileSettingsFormEventTest extends TestCase
{
	private function createEvent(): ProfileSettingsFormEvent
	{
		return new ProfileSettingsFormEvent(['name' => 'original'], '<p>original</p>');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(ProfileSettingsFormEvent::NAME, $event->getName());
	}

	public function testGetProfileArrayReturnsProfile(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['name' => 'original'], $event->getProfileArray());
	}

	public function testGetSetEntry(): void
	{
		$event = $this->createEvent();

		$this->assertSame('<p>original</p>', $event->getEntry());

		$event->setEntry('<p>modified</p>');

		$this->assertSame('<p>modified</p>', $event->getEntry());
	}
}
