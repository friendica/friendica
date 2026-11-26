<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\UserExportOptionsEvent;
use PHPUnit\Framework\TestCase;

class UserExportOptionsEventTest extends TestCase
{
	private function createEvent(): UserExportOptionsEvent
	{
		return new UserExportOptionsEvent([
			['settings/userexport/account', 'Export account', 'Export your account info and contacts.'],
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

		$this->assertSame(UserExportOptionsEvent::NAME, $event->getName());
	}

	public function testGetOptionsArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame([
			['settings/userexport/account', 'Export account', 'Export your account info and contacts.'],
		], $event->getOptionsArray());
	}

	public function testSetOptionsArray(): void
	{
		$event = $this->createEvent();

		$event->setOptionsArray([
			['settings/userexport/backup', 'Export all', 'Export your account info, contacts and all your items.'],
		]);

		$this->assertSame([
			['settings/userexport/backup', 'Export all', 'Export your account info, contacts and all your items.'],
		], $event->getOptionsArray());
	}
}
