<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\DisplaySettingsPostEvent;
use PHPUnit\Framework\TestCase;

class DisplaySettingsPostEventTest extends TestCase
{
	private function createEvent(): DisplaySettingsPostEvent
	{
		return new DisplaySettingsPostEvent([
			'theme' => 'frio',
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

		$this->assertSame(DisplaySettingsPostEvent::NAME, $event->getName());
	}

	public function testGetRequestArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame([
			'theme' => 'frio',
		], $event->getRequestArray());
	}
}
