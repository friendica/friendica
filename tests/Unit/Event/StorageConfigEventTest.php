<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Event\StorageConfigEvent;
use PHPUnit\Framework\TestCase;

class StorageConfigEventTest extends TestCase
{
	private function createEvent(): StorageConfigEvent
	{
		return new StorageConfigEvent('s3_storage');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(StorageConfigEvent::NAME, $event->getName());
	}

	public function testGetBackendNameReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('s3_storage', $event->getBackendName());
	}

	public function testGetConfigIsNullByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertNull($event->getConfig());
	}

	public function testSetConfig(): void
	{
		$event     = $this->createEvent();
		$configure = $this->createStub(ICanConfigureStorage::class);

		$event->setConfig($configure);

		$this->assertSame($configure, $event->getConfig());
	}
}
