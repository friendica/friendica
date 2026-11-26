<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\PhotoUploadEvent;
use PHPUnit\Framework\TestCase;

class PhotoUploadEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new PhotoUploadEvent('src', 'filename', 0, 'type');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PhotoUploadEvent('src', 'filename', 0, 'type');

		$this->assertSame(PhotoUploadEvent::NAME, $event->getName());
	}

	public function testGetters(): void
	{
		$event = new PhotoUploadEvent('/tmp/photo', 'photo.jpg', 12345, 'image/jpeg');

		$this->assertSame('/tmp/photo', $event->getSrc());
		$this->assertSame('photo.jpg', $event->getFilename());
		$this->assertSame(12345, $event->getFilesize());
		$this->assertSame('image/jpeg', $event->getType());
	}

	public function testSetters(): void
	{
		$event = new PhotoUploadEvent('', '', 0, '');

		$event->setSrc('/tmp/photo');
		$event->setFilename('photo.jpg');
		$event->setFilesize(12345);
		$event->setType('image/jpeg');

		$this->assertSame('/tmp/photo', $event->getSrc());
		$this->assertSame('photo.jpg', $event->getFilename());
		$this->assertSame(12345, $event->getFilesize());
		$this->assertSame('image/jpeg', $event->getType());
	}
}
