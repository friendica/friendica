<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\DbStructureDefinitionEvent;
use PHPUnit\Framework\TestCase;

class DbStructureDefinitionEventTest extends TestCase
{
	private function createEvent(): DbStructureDefinitionEvent
	{
		return new DbStructureDefinitionEvent([
			'user' => [
				'fields' => [
					'uid' => ['type' => 'int unsigned', 'primary' => '1'],
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

		$this->assertSame(DbStructureDefinitionEvent::NAME, $event->getName());
	}

	public function testGetDefinitionArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame([
			'user' => [
				'fields' => [
					'uid' => ['type' => 'int unsigned', 'primary' => '1'],
				],
			],
		], $event->getDefinitionArray());
	}

	public function testSetDefinitionArray(): void
	{
		$event = $this->createEvent();

		$event->setDefinitionArray([
			'rules' => [
				'fields' => [
					'id' => ['type' => 'int unsigned', 'primary' => '1'],
				],
			],
		]);

		$this->assertSame([
			'rules' => [
				'fields' => [
					'id' => ['type' => 'int unsigned', 'primary' => '1'],
				],
			],
		], $event->getDefinitionArray());
	}
}
