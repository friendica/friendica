<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\PhotoUploadStartEvent;
use PHPUnit\Framework\TestCase;

class PhotoUploadStartEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new PhotoUploadStartEvent([]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PhotoUploadStartEvent([]);

		$this->assertSame(PhotoUploadStartEvent::NAME, $event->getName());
	}

	public function testGetRequestArray(): void
	{
		$request = ['album' => 'Testalbum'];

		$event = new PhotoUploadStartEvent($request);

		$this->assertSame($request, $event->getRequestArray());
	}

	public function testSetRequestArray(): void
	{
		$event = new PhotoUploadStartEvent([]);

		$request = ['album' => 'Changed album'];

		$event->setRequestArray($request);

		$this->assertSame($request, $event->getRequestArray());
	}
}
