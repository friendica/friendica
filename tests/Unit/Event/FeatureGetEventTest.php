<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\FeatureGetEvent;
use PHPUnit\Framework\TestCase;

class FeatureGetEventTest extends TestCase
{
	private function createEvent(): FeatureGetEvent
	{
		return new FeatureGetEvent([
			'general' => ['General Settings', [['expanding', 'Expanding Events', 'Provide the ability to expand posts', true, false]]],
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

		$this->assertSame(FeatureGetEvent::NAME, $event->getName());
	}

	public function testGetFeaturesReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame([
			'general' => ['General Settings', [['expanding', 'Expanding Events', 'Provide the ability to expand posts', true, false]]],
		], $event->getFeatures());
	}

	public function testSetFeatures(): void
	{
		$event = $this->createEvent();

		$event->setFeatures([
			'network' => ['Network Widgets', [['circles', 'Circles', 'Display posts of the selected circle', true, false]]],
		]);

		$this->assertSame([
			'network' => ['Network Widgets', [['circles', 'Circles', 'Display posts of the selected circle', true, false]]],
		], $event->getFeatures());
	}
}
