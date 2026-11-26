<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\DbViewDefinitionEvent;
use PHPUnit\Framework\TestCase;

class DbViewDefinitionEventTest extends TestCase
{
	private function createEvent(): DbViewDefinitionEvent
	{
		return new DbViewDefinitionEvent([
			'user-view' => [
				'fields' => [
					'uid' => ['type' => 'int unsigned'],
				],
			],
		]);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(DbViewDefinitionEvent::NAME, $event->getName());
	}

	public function testGetDefinitionArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame([
			'user-view' => [
				'fields' => [
					'uid' => ['type' => 'int unsigned'],
				],
			],
		], $event->getDefinitionArray());
	}

	public function testSetDefinitionArray(): void
	{
		$event = $this->createEvent();

		$event->setDefinitionArray([
			'post-view' => [
				'fields' => [
					'id' => ['type' => 'int unsigned'],
				],
			],
		]);

		$this->assertSame([
			'post-view' => [
				'fields' => [
					'id' => ['type' => 'int unsigned'],
				],
			],
		], $event->getDefinitionArray());
	}
}
