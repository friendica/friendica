<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\ModuleContentEvent;
use Friendica\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class ModuleContentEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new ModuleContentEvent('test', 'moduleName', \stdClass::class, 'content');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public static function getPublicConstants(): array
	{
		return [
			[ModuleContentEvent::MODULE_CONTENT, 'friendica.module_content'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getPublicConstants')]
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ModuleContentEvent('test', 'moduleName', \stdClass::class, 'content');

		$this->assertSame('test', $event->getName());
	}

	public function testGetModuleNameReturnsModuleName(): void
	{
		$event = new ModuleContentEvent('test', 'moduleName', \stdClass::class, 'content');

		$this->assertSame('moduleName', $event->getModuleName());
	}

	public function testGetModuleClassReturnsModuleClass(): void
	{
		$event = new ModuleContentEvent('test', 'moduleName', \stdClass::class, 'content');

		$this->assertSame(\stdClass::class, $event->getModuleClass());
	}

	public function testGetContentReturnsCorrectString(): void
	{
		$event = new ModuleContentEvent('test', 'moduleName', \stdClass::class, 'myContent');

		$this->assertSame('myContent', $event->getContent());
	}

	public function testSetContentUpdatesContent(): void
	{
		$event = new ModuleContentEvent('test', 'moduleName', \stdClass::class, 'oldContent');

		$event->setContent('newContent');

		$this->assertSame('newContent', $event->getContent());
	}
}
