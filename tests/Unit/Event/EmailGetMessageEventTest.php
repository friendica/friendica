<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\EmailGetMessageEvent;
use PHPUnit\Framework\TestCase;

class EmailGetMessageEventTest extends TestCase
{
	private function createEvent(): EmailGetMessageEvent
	{
		return new EmailGetMessageEvent('Text', 'Html', ['body' => 'Body']);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(EmailGetMessageEvent::NAME, $event->getName());
	}

	public function testGetTextReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('Text', $event->getText());
	}

	public function testSetText(): void
	{
		$event = $this->createEvent();

		$event->setText('New Text');

		$this->assertSame('New Text', $event->getText());
	}

	public function testGetHtmlReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('Html', $event->getHtml());
	}

	public function testSetHtml(): void
	{
		$event = $this->createEvent();

		$event->setHtml('New Html');

		$this->assertSame('New Html', $event->getHtml());
	}

	public function testGetItemArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['body' => 'Body'], $event->getItemArray());
	}

	public function testGetItemArrayIsEmptyByDefault(): void
	{
		$event = new EmailGetMessageEvent('Text', 'Html');

		$this->assertSame([], $event->getItemArray());
	}

	public function testSetItemArray(): void
	{
		$event = $this->createEvent();

		$event->setItemArray(['body' => 'New Body']);

		$this->assertSame(['body' => 'New Body'], $event->getItemArray());
	}
}
