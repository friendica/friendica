<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Friendica;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Notification;
use Friendica\Test\ApiTestCase;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

class NotificationTest extends ApiTestCase
{
	public function testEmpty()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		$this->expectException(BadRequestException::class);
		DI::session()->set('uid', '');

		Notification::rawContent();
		*/
	}

	public function testWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		$this->expectException(BadRequestException::class);
		DI::session()->set('uid', 41);

		Notification::rawContent();
		*/
	}

	public function testWithXmlResult()
	{
		$date    = DateTimeFormat::local('2020-01-01 12:12:02');
		$dateRel = Temporal::getRelativeDate('2020-01-01 12:12:02');

		$assertXml = <<<XML
<?xml version="1.0"?>
<notes>
  <note date="$date" date_rel="$dateRel" id="1" iid="4" link="https://friendica.local/display/1" msg="A test reply from an item" msg_cache="A test reply from an item" msg_html="A test reply from an item" msg_plain="A test reply from an item" name="Friend contact" name_cache="Friend contact" otype="item" parent="" photo="https://friendica.local/" seen="false" timestamp="1577880722" type="8" uid="42" url="https://friendica.local/profile/friendcontact" verb="http://activitystrea.ms/schema/1.0/post"/>
</notes>
XML;

		$response = (new Notification(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']))
			->run($this->httpExceptionMock);

		self::assertXmlStringEqualsXmlString($assertXml, (string)$response->getBody());
		self::assertEquals([
			'Content-type'                => ['text/xml'],
			ICanCreateResponses::X_HEADER => ['xml']
		], $response->getHeaders());
	}

	public function testWithJsonResult()
	{
		$response = (new Notification(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertIsArray($json);

		foreach ($json as $note) {
			self::assertIsInt($note->id);
			self::assertIsInt($note->uid);
			self::assertIsString($note->msg);
		}

		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json']
		], $response->getHeaders());
	}
}
