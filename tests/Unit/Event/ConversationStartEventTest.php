<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\ConversationStartEvent;
use PHPUnit\Framework\TestCase;

class ConversationStartEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new ConversationStartEvent([['id' => 1]], 'mode', true, false);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ConversationStartEvent([['id' => 1]], 'mode', true, false);

		$this->assertSame(ConversationStartEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new ConversationStartEvent([['id' => 1]], 'mode', true, false);

		$this->assertSame([['id' => 1]], $event->getItemsArray());
		$this->assertSame('mode', $event->getMode());
		$this->assertTrue($event->isUpdate());
		$this->assertFalse($event->isPreview());

		$event->setItemsArray([['id' => 2]]);

		$this->assertSame([['id' => 2]], $event->getItemsArray());
	}
}
