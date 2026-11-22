<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Friendica;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Notification;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;
use GuzzleHttp\Psr7\ServerRequest;

final class NotificationTest extends ApiTestCase
{
	public function testApiNotificationWithXmlResult(): void
	{
		$date    = DateTimeFormat::local('2020-01-01 12:12:02');
		$dateRel = Temporal::getRelativeDate('2020-01-01 12:12:02');

		$assertXml = <<<XML
<?xml version="1.0"?>
<notes>
  <note date="$date" date_rel="$dateRel" id="501" iid="1504" link="https://friendica.local/display/1" msg="A test reply from an item" msg_cache="A test reply from an item" msg_html="A test reply from an item" msg_plain="A test reply from an item" name="Friend contact" name_cache="Friend contact" otype="item" parent="" photo="https://friendica.local/" seen="false" timestamp="1577880722" type="8" uid="1042" url="https://friendica.local/profile/friendcontact" verb="http://activitystrea.ms/schema/1.0/post"/>
</notes>
XML;

		$request = new ServerRequest('GET', 'https://friendica.local/api/friendica/notification');

		$response = $this->createModule(['extension' => 'xml'])->handleRequest($request);

		self::assertXmlStringEqualsXmlString($assertXml, (string) $response->getBody());
		self::assertEquals([
			'Content-type'                => ['text/xml'],
			ICanCreateResponses::X_HEADER => ['xml'],
		], $response->getHeaders());
	}

	public function testApiNotificationWithJsonResult(): void
	{
		$request = new ServerRequest('GET', 'https://friendica.local/api/friendica/notification');

		$response = $this->createModule(['extension' => 'json'])->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertCount(1, $json);

		foreach ($json as $note) {
			self::assertIsInt($note->id);
			self::assertIsInt($note->uid);
			self::assertIsString($note->msg);
		}
	}

	public function testApiNotificationWithEmptyList(): void
	{
		DI::dba()->delete('notify', ['uid' => 1042]);

		$request = new ServerRequest('GET', 'https://friendica.local/api/friendica/notification');

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertFalse($json);
	}

	public function testApiNotificationWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = new ServerRequest('GET', 'https://friendica.local/api/friendica/notification');

		$module = $this->createModule();

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(array $parameters = []): Notification
	{
		return new Notification(
			DI::mstdnError(),
			DI::appHelper(),
			DI::l10n(),
			DI::baseUrl(),
			DI::args(),
			DI::logger(),
			DI::profiler(),
			DI::apiResponse(),
			[],
			$parameters,
		);
	}
}
