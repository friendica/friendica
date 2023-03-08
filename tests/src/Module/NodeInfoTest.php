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

namespace Friendica\Test\src\Module;

use Friendica\App;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\NodeInfo110;
use Friendica\Module\NodeInfo120;
use Friendica\Module\NodeInfo210;
use Friendica\Module\Special\HTTPException;
use Friendica\Test\FixtureTest;
use Mockery\MockInterface;

class NodeInfoTest extends FixtureTest
{
	/** @var MockInterface|HTTPException */
	protected $httpExceptionMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpExceptionMock = \Mockery::mock(HTTPException::class);
	}

	public function testNodeInfo110()
	{
		$response = (new NodeInfo110(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []))
			->run($this->httpExceptionMock);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody());

		self::assertEquals('1.0', $json->version);

		self::assertEquals('friendica', $json->software->name);
		self::assertEquals(App::VERSION . '-' . DB_UPDATE_VERSION, $json->software->version);

		self::assertIsArray($json->protocols->inbound);
		self::assertIsArray($json->protocols->outbound);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}

	public function testNodeInfo120()
	{
		$response = (new NodeInfo120(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []))
			->run($this->httpExceptionMock);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody());

		self::assertEquals('2.0', $json->version);

		self::assertEquals('friendica', $json->software->name);
		self::assertEquals(App::VERSION . '-' . DB_UPDATE_VERSION, $json->software->version);

		self::assertIsArray($json->protocols);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}

	public function testNodeInfo210()
	{
		$response = (new NodeInfo210(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []))
			->run($this->httpExceptionMock);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody());

		self::assertEquals('1.0', $json->version);

		self::assertEquals('friendica', $json->server->software);
		self::assertEquals(App::VERSION . '-' . DB_UPDATE_VERSION, $json->server->version);

		self::assertIsArray($json->protocols);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}
}
