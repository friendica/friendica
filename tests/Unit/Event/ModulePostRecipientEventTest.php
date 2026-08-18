<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\ModulePostRecipientEvent;
use Friendica\Core\Event\NamedEvent;
use Friendica\Module\Smilies;
use PHPUnit\Framework\TestCase;

class ModulePostRecipientEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new ModulePostRecipientEvent('moduleName', Smilies::class, 'html');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public static function getPublicConstants(): array
	{
		return [
			[ModulePostRecipientEvent::NAME, 'friendica.module_post_recipient'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getPublicConstants')]
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ModulePostRecipientEvent('moduleName', Smilies::class, 'html');

		$this->assertSame('friendica.module_post_recipient', $event->getName());
	}

	public function testGetModuleNameReturnsModuleName(): void
	{
		$event = new ModulePostRecipientEvent('moduleName', Smilies::class, 'html');

		$this->assertSame('moduleName', $event->getModuleName());
	}

	public function testGetModuleClassReturnsModuleClass(): void
	{
		$event = new ModulePostRecipientEvent('moduleName', Smilies::class, 'html');

		$this->assertSame(Smilies::class, $event->getModuleClass());
	}

	public function testGetHtmlReturnsCorrectString(): void
	{
		$event = new ModulePostRecipientEvent('moduleName', Smilies::class, 'myHtml');

		$this->assertSame('myHtml', $event->getHtml());
	}

	public function testSetHtmlUpdatesHtml(): void
	{
		$event = new ModulePostRecipientEvent('moduleName', Smilies::class, 'oldHtml');

		$event->setHtml('newHtml');

		$this->assertSame('newHtml', $event->getHtml());
	}
}
