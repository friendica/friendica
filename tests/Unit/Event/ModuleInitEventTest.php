<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\ModuleInitEvent;
use Friendica\Core\Event\NamedEvent;
use Friendica\Module\Smilies;
use PHPUnit\Framework\TestCase;

class ModuleInitEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new ModuleInitEvent('test', 'moduleName', Smilies::class);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public static function getPublicConstants(): array
	{
		return [
			[ModuleInitEvent::MODULE_INIT, 'friendica.module_init'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getPublicConstants')]
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ModuleInitEvent('test', 'moduleName', Smilies::class);

		$this->assertSame('test', $event->getName());
	}

	public function testGetModuleNameReturnsModuleName(): void
	{
		$event = new ModuleInitEvent('test', 'moduleName', Smilies::class);

		$this->assertSame('moduleName', $event->getModuleName());
	}

	public function testGetModuleClassReturnsModuleClass(): void
	{
		$event = new ModuleInitEvent('test', 'moduleName', Smilies::class);

		$this->assertSame(Smilies::class, $event->getModuleClass());
	}
}
