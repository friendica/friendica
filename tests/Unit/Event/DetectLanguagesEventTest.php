<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\DetectLanguagesEvent;
use PHPUnit\Framework\TestCase;

class DetectLanguagesEventTest extends TestCase
{
	private function createEvent(): DetectLanguagesEvent
	{
		return new DetectLanguagesEvent('This is some text', ['en' => 0.8], 42, 99);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(DetectLanguagesEvent::NAME, $event->getName());
	}

	public function testGetTextReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('This is some text', $event->getText());
	}

	public function testGetDetectedReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['en' => 0.8], $event->getDetected());
	}

	public function testSetDetected(): void
	{
		$event = $this->createEvent();

		$event->setDetected(['de' => 0.9]);

		$this->assertSame(['de' => 0.9], $event->getDetected());
	}

	public function testGetUriIdAndAuthorIdReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame(42, $event->getUriId());
		$this->assertSame(99, $event->getAuthorId());
	}
}
