<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\EditContactFormEvent;
use PHPUnit\Framework\TestCase;

class EditContactFormEventTest extends TestCase
{
	private function createEvent(): EditContactFormEvent
	{
		return new EditContactFormEvent(['name' => 'original'], '<p>original</p>');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(EditContactFormEvent::NAME, $event->getName());
	}

	public function testGetContactArrayReturnsContact(): void
	{
		$event = $this->createEvent();

		$this->assertSame(['name' => 'original'], $event->getContactArray());
	}

	public function testGetSetOutput(): void
	{
		$event = $this->createEvent();

		$this->assertSame('<p>original</p>', $event->getOutput());

		$event->setOutput('<p>modified</p>');

		$this->assertSame('<p>modified</p>', $event->getOutput());
	}
}
