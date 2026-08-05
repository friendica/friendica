<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\DisplayItemEvent;
use PHPUnit\Framework\TestCase;

class DisplayItemEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new DisplayItemEvent(['id' => 1], ['template' => 'wall_thread.tpl']);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new DisplayItemEvent(['id' => 1], ['template' => 'wall_thread.tpl']);

		$this->assertSame(DisplayItemEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new DisplayItemEvent(['id' => 1], ['template' => 'wall_thread.tpl']);

		$this->assertSame(['id' => 1], $event->getItemArray());
		$this->assertSame(['template' => 'wall_thread.tpl'], $event->getTemplateDataArray());

		$event->setTemplateDataArray(['template' => 'wall_item.tpl']);

		$this->assertSame(['template' => 'wall_item.tpl'], $event->getTemplateDataArray());
	}
}
