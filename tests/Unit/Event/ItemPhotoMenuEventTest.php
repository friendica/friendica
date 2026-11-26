<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\ItemPhotoMenuEvent;
use PHPUnit\Framework\TestCase;

class ItemPhotoMenuEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new ItemPhotoMenuEvent(['id' => 1], ['View Profile' => 'https://example.com']);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ItemPhotoMenuEvent(['id' => 1], ['View Profile' => 'https://example.com']);

		$this->assertSame(ItemPhotoMenuEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new ItemPhotoMenuEvent(['id' => 1], ['View Profile' => 'https://example.com']);

		$this->assertSame(['id' => 1], $event->getItemArray());
		$this->assertSame(['View Profile' => 'https://example.com'], $event->getMenuArray());

		$event->setMenuArray(['View Profile' => 'https://example.com', 'Block user' => 'https://example.com/block']);

		$this->assertSame(['View Profile' => 'https://example.com', 'Block user' => 'https://example.com/block'], $event->getMenuArray());
	}
}
