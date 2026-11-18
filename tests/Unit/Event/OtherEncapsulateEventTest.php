<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\OtherEncapsulateEvent;
use PHPUnit\Framework\TestCase;

class OtherEncapsulateEventTest extends TestCase
{
	private function createEvent(): OtherEncapsulateEvent
	{
		return new OtherEncapsulateEvent('data', 'pubkey', 'aes256cbc');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(OtherEncapsulateEvent::NAME, $event->getName());
	}

	public function testGetDataReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('data', $event->getData());
	}

	public function testGetPubkeyReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('pubkey', $event->getPubkey());
	}

	public function testGetAlgReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('aes256cbc', $event->getAlg());
	}

	public function testGetResultReturnsDataByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertSame('data', $event->getResult());
	}

	public function testSetResult(): void
	{
		$event = $this->createEvent();

		$event->setResult('encrypted');

		$this->assertSame('encrypted', $event->getResult());
	}
}
