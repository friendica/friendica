<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Friendica\Events;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Events\Delete;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Protocol\Delivery;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class DeleteTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	/**
	 * Attaches an event of user 1042 to item 1503 and returns the event id.
	 */
	private function addEvent(): int
	{
		DI::dba()->insert('event', ['uid' => 1042, 'cid' => 3042, 'type' => 'event', 'summary' => 'Event', 'start' => '2026-11-17 10:00:00']);
		$eventId = DI::dba()->lastInsertId();
		DI::dba()->update('post-user', ['event-id' => $eventId], ['id' => 1503]);

		return $eventId;
	}

	private function delete(int $eventId): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/event_delete'))
			->withParsedBody(['id' => $eventId]);

		(new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->handleRequest($request);
	}

	public function testDeleteAlsoDeletesTheEventPost(): void
	{
		$eventId = $this->addEvent();

		$this->delete($eventId);

		self::assertFalse(DI::dba()->exists('event', ['id' => $eventId]));
		self::assertTrue(DI::dba()->exists('post-user', ['id' => 1503, 'deleted' => true]));
		self::assertTrue(DI::dba()->exists('post-user', ['id' => 1509, 'deleted' => true]));
		self::assertTrue(DI::dba()->exists('workerqueue', ['command' => 'Notifier', 'parameter' => json_encode([Delivery::DELETION, 20003, 1042])]));
	}

	public function testDeleteRemovesEventWithoutPost(): void
	{
		DI::dba()->insert('event', ['uid' => 1042, 'cid' => 3042, 'type' => 'event', 'summary' => 'Event', 'start' => '2026-11-17 10:00:00']);
		$eventId = DI::dba()->lastInsertId();

		$this->delete($eventId);

		self::assertFalse(DI::dba()->exists('event', ['id' => $eventId]));
		self::assertFalse(DI::dba()->exists('post-user', ['deleted' => true]));
	}

	public function testDeleteRejectsEventOfOtherUser(): void
	{
		DI::dba()->insert('user', ['uid' => 1043, 'nickname' => 'other']);
		DI::dba()->insert('event', ['uid' => 1043, 'cid' => 3042, 'type' => 'event', 'summary' => 'Event', 'start' => '2026-11-17 10:00:00']);
		$eventId = DI::dba()->lastInsertId();

		$this->expectException(BadRequestException::class);

		$this->delete($eventId);
	}
}
