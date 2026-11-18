<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\FeatureEnabledEvent;
use PHPUnit\Framework\TestCase;

class FeatureEnabledEventTest extends TestCase
{
	private function createEvent(): FeatureEnabledEvent
	{
		return new FeatureEnabledEvent(42, 'expanding_events', true);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(FeatureEnabledEvent::NAME, $event->getName());
	}

	public function testGetUidAndGetFeatureReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame(42, $event->getUid());
		$this->assertSame('expanding_events', $event->getFeature());
	}

	public function testIsEnabledReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertTrue($event->isEnabled());
	}

	public function testSetEnabled(): void
	{
		$event = $this->createEvent();

		$event->setEnabled(false);

		$this->assertFalse($event->isEnabled());
	}
}
