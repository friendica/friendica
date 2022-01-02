<?php

namespace Friendica\Test\src\Module;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\NodeInfo110;
use Friendica\Module\NodeInfo120;
use Friendica\Module\NodeInfo210;
use Friendica\Module\Response;
use Friendica\Test\FixtureTest;

class NodeInfoTest extends FixtureTest
{
	public function testNodeInfo110()
	{
		$response = (new NodeInfo110(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []))
			->run();

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody());

		self::assertEquals('1.0', $json->version);

		self::assertEquals('friendica', $json->software->name);
		self::assertEquals(FRIENDICA_VERSION . '-' . DB_UPDATE_VERSION, $json->software->version);

		self::assertIsArray($json->protocols->inbound);
		self::assertIsArray($json->protocols->outbound);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}

	public function testNodeInfo120()
	{
		$response = (new NodeInfo120(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []))
			->run();

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody());

		self::assertEquals('2.0', $json->version);

		self::assertEquals('friendica', $json->software->name);
		self::assertEquals(FRIENDICA_VERSION . '-' . DB_UPDATE_VERSION, $json->software->version);

		self::assertIsArray($json->protocols);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}

	public function testNodeInfo210()
	{
		$response = (new NodeInfo210(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []))
			->run();

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody());

		self::assertEquals('1.0', $json->version);

		self::assertEquals('friendica', $json->server->software);
		self::assertEquals(FRIENDICA_VERSION . '-' . DB_UPDATE_VERSION, $json->server->version);

		self::assertIsArray($json->protocols);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}
}
