<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Calendar\Event;

use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Event;
use Friendica\Module\Calendar\Event\API;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * The delete, ignore and unignore actions are GET links and need a form security token.
 */
final class APITokenTest extends FixtureTestCase
{
	private const USER_ID = 42; // Local user from api.fixture.php.

	protected function setUp(): void
	{
		parent::setUp();

		DI::userSession()->set('authenticated', true);
		DI::userSession()->set('uid', self::USER_ID);
	}

	/**
	 * Adds an event of user 42 attached to item 3 and returns the event id.
	 */
	private function addEvent(): int
	{
		DI::dba()->insert('event', ['uid' => self::USER_ID, 'cid' => 42, 'type' => 'event', 'summary' => 'Event', 'start' => '2026-11-17 10:00:00']);
		$eventId = DI::dba()->lastInsertId();
		DI::dba()->update('post-user', ['event-id' => $eventId], ['id' => 3]);

		return $eventId;
	}

	/** @param array<string, string> $query */
	private function call(string $action, int $eventId, array $query): void
	{
		$module = new API(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), new Response(), DI::userSession(), DI::sysmsg(), DI::aclFormatter(), DI::appHelper(), DI::formSecurityToken(), [], ['action' => $action, 'id' => $eventId]);

		try {
			$module->handleRequest((new ServerRequest(Router::GET, DI::baseUrl() . '/calendar/api/' . $action . '/' . $eventId))->withQueryParams($query));
		} catch (FoundException) {
			// Every action ends with a redirect to the calendar.
		}
	}

	public function testDeleteWithTokenDeletesTheEvent(): void
	{
		$eventId = $this->addEvent();

		$this->call(API::ACTION_DELETE, $eventId, ['t' => BaseModule::getFormSecurityToken(Event::ACTION_TOKEN_TYPE)]);

		self::assertFalse(DI::dba()->exists('event', ['id' => $eventId]));
	}

	public function testDeleteWithoutTokenIsForbidden(): void
	{
		$eventId = $this->addEvent();

		try {
			$this->call(API::ACTION_DELETE, $eventId, []);
			self::fail('Expected a ForbiddenException');
		} catch (ForbiddenException) {
			// expected
		}

		self::assertTrue(DI::dba()->exists('event', ['id' => $eventId]));
	}

	public function testIgnoreWithTokenOfOtherFormIsForbidden(): void
	{
		$eventId = $this->addEvent();

		try {
			$this->call(API::ACTION_IGNORE, $eventId, ['t' => BaseModule::getFormSecurityToken('other_form')]);
			self::fail('Expected a ForbiddenException');
		} catch (ForbiddenException) {
			// expected
		}

		self::assertTrue(DI::dba()->exists('event', ['id' => $eventId, 'ignore' => false]));
	}

	public function testIgnoreWithTokenIgnoresTheEvent(): void
	{
		$eventId = $this->addEvent();

		$this->call(API::ACTION_IGNORE, $eventId, ['t' => BaseModule::getFormSecurityToken(Event::ACTION_TOKEN_TYPE)]);

		self::assertTrue(DI::dba()->exists('event', ['id' => $eventId, 'ignore' => true]));
	}

	public function testDeleteLinkCarriesAValidToken(): void
	{
		$eventId = $this->addEvent();

		$event = Event::prepareForItem(Event::getByIdAndUid(self::USER_ID, $eventId));

		parse_str((string) parse_url((string) $event['drop'][0], PHP_URL_QUERY), $query);
		self::assertStringStartsWith('calendar/api/delete/' . $eventId . '?t=', $event['drop'][0]);
		self::assertTrue(DI::formSecurityToken()->isValid($query['t'], Event::ACTION_TOKEN_TYPE));
	}
}
