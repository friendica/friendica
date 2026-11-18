<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ModerationUsersTabsEvent;
use PHPUnit\Framework\TestCase;

class ModerationUsersTabsEventTest extends TestCase
{
	private function createEvent(): ModerationUsersTabsEvent
	{
		return new ModerationUsersTabsEvent(
			[
				[
					'label'     => 'Users',
					'url'       => 'moderation/users',
					'sel'       => 'active',
					'title'     => 'List of users',
					'id'        => 'admin-users',
					'accesskey' => 'u',
				],
			],
			'users',
		);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(ModerationUsersTabsEvent::NAME, $event->getName());
	}

	public function testGettersReturnValues(): void
	{
		$event = $this->createEvent();

		$this->assertSame(
			[
				[
					'label'     => 'Users',
					'url'       => 'moderation/users',
					'sel'       => 'active',
					'title'     => 'List of users',
					'id'        => 'admin-users',
					'accesskey' => 'u',
				],
			],
			$event->getTabsArray(),
		);
		$this->assertSame('users', $event->getSelectedTab());
	}

	public function testSetTabs(): void
	{
		$event = $this->createEvent();

		$event->setTabsArray([
			[
				'label'     => 'Pending',
				'url'       => 'moderation/users/pending',
				'sel'       => '',
				'title'     => 'List of pending registrations',
				'id'        => 'admin-users-pending',
				'accesskey' => 'p',
			],
		]);

		$this->assertSame(
			[
				[
					'label'     => 'Pending',
					'url'       => 'moderation/users/pending',
					'sel'       => '',
					'title'     => 'List of pending registrations',
					'id'        => 'admin-users-pending',
					'accesskey' => 'p',
				],
			],
			$event->getTabsArray(),
		);
	}
}
