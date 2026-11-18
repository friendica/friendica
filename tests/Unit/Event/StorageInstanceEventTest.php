<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Event\StorageInstanceEvent;
use PHPUnit\Framework\TestCase;

class StorageInstanceEventTest extends TestCase
{
	private function createEvent(): StorageInstanceEvent
	{
		return new StorageInstanceEvent('s3_storage');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(StorageInstanceEvent::NAME, $event->getName());
	}

	public function testGetBackendNameReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('s3_storage', $event->getBackendName());
	}

	public function testGetStorageIsNullByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertNull($event->getStorage());
	}

	public function testSetStorage(): void
	{
		$event   = $this->createEvent();
		$storage = $this->createStub(ICanReadFromStorage::class);

		$event->setStorage($storage);

		$this->assertSame($storage, $event->getStorage());
	}
}
