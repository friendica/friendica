<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\OtherUnencapsulateEvent;
use PHPUnit\Framework\TestCase;

class OtherUnencapsulateEventTest extends TestCase
{
	private function createEvent(): OtherUnencapsulateEvent
	{
		return new OtherUnencapsulateEvent(['key' => 'value'], 'prvkey', 'aes256cbc');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(OtherUnencapsulateEvent::NAME, $event->getName());
	}

	public function testGetDataArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['key' => 'value'], $event->getDataArray());
	}

	public function testGetPrivateKeyReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('prvkey', $event->getPrivateKey());
	}

	public function testGetAlgReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('aes256cbc', $event->getAlg());
	}

	public function testGetResultArrayReturnsDataByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['key' => 'value'], $event->getResultArray());
	}

	public function testSetResultArray(): void
	{
		$event = $this->createEvent();

		$event->setResultArray(['data' => 'decrypted']);

		$this->assertSame(['data' => 'decrypted'], $event->getResultArray());
	}
}
