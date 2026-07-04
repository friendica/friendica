<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module;

use Friendica\App;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\NodeInfo110;
use Friendica\Module\NodeInfo120;
use Friendica\Module\NodeInfo210;
use Friendica\Module\Special\HTTPException;
use Friendica\Test\FixtureTestCase;
use Mockery\MockInterface;

class NodeInfoTest extends FixtureTestCase
{
	/** @var MockInterface|HTTPException */
	protected $httpExceptionMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpExceptionMock = \Mockery::mock(HTTPException::class);
	}

	public function testNodeInfo110(): void
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

	public function testNodeInfo120(): void
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

	public function testNodeInfo210(): void
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
