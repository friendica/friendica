<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Calendar\Event;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\Calendar\Event\API;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;

class APITest extends FixtureTestCase
{
	private const UID = 42;

	/**
	 * Attaches an event to item 3 of user 42 and returns the event id.
	 */
	private function addEvent(string $type, int $uid = self::UID): int
	{
		DBA::insert('event', ['uid' => $uid, 'cid' => 42, 'type' => $type, 'summary' => 'Event', 'start' => '2026-11-17 10:00:00']);
		$eventId = DBA::lastInsertId();

		DBA::update('post-user', ['event-id' => $eventId], ['id' => 3]);

		return $eventId;
	}

	private function delete(int $eventId): void
	{
		$session = $this->createStub(IHandleUserSessions::class);
		$session->method('getLocalUserId')->willReturn(self::UID);

		$args = $this->createStub(Arguments::class);
		$args->method('getMethod')->willReturn('GET');
		$args->method('getQueryString')->willReturn('calendar/api/delete/' . $eventId);

		$module = new API(
			DI::l10n(),
			$this->createStub(BaseURL::class),
			$args,
			DI::logger(),
			DI::profiler(),
			new Response(),
			$session,
			DI::sysmsg(),
			DI::aclFormatter(),
			DI::appHelper(),
			DI::postDeletion(),
			[],
			['action' => API::ACTION_DELETE, 'id' => $eventId],
		);

		$module->handleRequest(new ServerRequest('GET', 'https://friendica.local/calendar/api/delete/' . $eventId));
	}

	public function testDeleteRemovesTheEventItem(): void
	{
		$this->delete($this->addEvent('event'));

		// Deleting the event cascades to the user item, the public copy is only marked
		self::assertFalse(DBA::exists('post-user', ['id' => 3]));
		self::assertTrue(DBA::exists('post-user', ['id' => 9, 'deleted' => true]));
		self::assertSame([], DI::sysmsg()->flushNotices());
	}

	public function testDeleteKeepsBirthdays(): void
	{
		$this->delete($this->addEvent('birthday'));

		self::assertTrue(DBA::exists('post-user', ['id' => 3, 'deleted' => false]));
		self::assertSame(['Failed to remove event'], DI::sysmsg()->flushNotices());
	}

	public function testDeleteRejectsEventOfOtherUser(): void
	{
		DBA::insert('user', ['uid' => 43, 'nickname' => 'other']);
		$eventId = $this->addEvent('event', 43);

		$this->expectException(NotFoundException::class);

		$this->delete($eventId);
	}
}
