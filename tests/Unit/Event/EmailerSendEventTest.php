<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\EmailerSendEvent;
use PHPUnit\Framework\TestCase;

class EmailerSendEventTest extends TestCase
{
	private function createEvent(): EmailerSendEvent
	{
		return new EmailerSendEvent('recipient@example.com', 'Subject', 'Body', 'Header', '-f sender@example.com');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(EmailerSendEvent::NAME, $event->getName());
	}

	public function testGetToAddressReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('recipient@example.com', $event->getToAddress());
	}

	public function testGetSubjectReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('Subject', $event->getSubject());
	}

	public function testGetBodyReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('Body', $event->getBody());
	}

	public function testGetHeadersReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('Header', $event->getHeaders());
	}

	public function testGetParametersReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('-f sender@example.com', $event->getParameters());
	}

	public function testGetParametersCanBeNull(): void
	{
		$event = new EmailerSendEvent('recipient@example.com', 'Subject', 'Body', 'Header', null);

		$this->assertNull($event->getParameters());
	}

	public function testIsSentIsFalseByDefault(): void
	{
		$event = $this->createEvent();

		$this->assertFalse($event->isSent());
	}

	public function testSetSent(): void
	{
		$event = $this->createEvent();

		$event->setSent(true);

		$this->assertTrue($event->isSent());
	}
}
