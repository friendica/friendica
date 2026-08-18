<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\OcrDetectionEvent;
use PHPUnit\Framework\TestCase;

class OcrDetectionEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new OcrDetectionEvent('img_str');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new OcrDetectionEvent('img_str');

		$this->assertSame(OcrDetectionEvent::NAME, $event->getName());
	}

	public function testGetImgStr(): void
	{
		$event = new OcrDetectionEvent('binary data');

		$this->assertSame('binary data', $event->getImgStr());
	}

	public function testGetDescription(): void
	{
		$event = new OcrDetectionEvent('binary data');

		$this->assertNull($event->getDescription());
	}

	public function testSetDescription(): void
	{
		$event = new OcrDetectionEvent('binary data');

		$event->setDescription('A photo of a cat');

		$this->assertSame('A photo of a cat', $event->getDescription());
	}
}
