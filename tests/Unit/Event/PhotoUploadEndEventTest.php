<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\PhotoUploadEndEvent;
use PHPUnit\Framework\TestCase;

class PhotoUploadEndEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new PhotoUploadEndEvent('');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PhotoUploadEndEvent('');

		$this->assertSame(PhotoUploadEndEvent::NAME, $event->getName());
	}

	public function testGetId(): void
	{
		$event = new PhotoUploadEndEvent('abc123');

		$this->assertSame('abc123', $event->getId());
	}
}
