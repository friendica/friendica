<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Module\Api\Friendica;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Notification;
use Friendica\Test\src\Module\Api\ApiTest;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

class NotificationTest extends ApiTest
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
		$dateRel = Temporal::getRelativeDate('2020-01-01 07:12:02');

		$assertXml = <<<XML
<?xml version="1.0"?>
<notes>
  <note date="$date" date_rel="$dateRel" id="1" iid="4" link="http://localhost/display/1" msg="A test reply from an item" msg_cache="A test reply from an item" msg_html="A test reply from an item" msg_plain="A test reply from an item" name="Friend contact" name_cache="Friend contact" otype="item" parent="" photo="http://localhost/" seen="false" timestamp="1577880722" type="8" uid="42" url="http://localhost/profile/friendcontact" verb="http://activitystrea.ms/schema/1.0/post"/>
</notes>
XML;

		$response = (new Notification(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']))
			->run($this->httpExceptionMock);

		self::assertXmlStringEqualsXmlString($assertXml, (string)$response->getBody());
		self::assertEquals([
			'Content-type'                => ['text/xml'],
			ICanCreateResponses::X_HEADER => ['xml']
		], $response->getHeaders());
	}

	public function testWithJsonResult()
	{
		$response = (new Notification(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
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
