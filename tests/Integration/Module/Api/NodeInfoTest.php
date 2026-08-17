<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api;

use Friendica\App;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\NodeInfo110;
use Friendica\Module\NodeInfo120;
use Friendica\Module\NodeInfo210;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class NodeInfoTest extends ApiTestCase
{
	public function testNodeInfo110(): void
	{
		$module = new NodeInfo110(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []);

		$response = $module->handleRequest(new ServerRequest('GET', 'https://friendica.local/nodeinfo/1.0'));

		self::assertJson((string) $response->getBody());
		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());

		$json = json_decode((string) $response->getBody());

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
		$module = new NodeInfo120(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []);

		$response = $module->handleRequest(new ServerRequest('GET', 'https://friendica.local/nodeinfo/2.0'));

		self::assertJson((string) $response->getBody());
		self::assertEquals([
			'Content-type'                => ['application/json; charset=utf-8'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());

		$json = json_decode((string) $response->getBody());

		self::assertEquals('2.0', $json->version);

		self::assertEquals('friendica', $json->software->name);
		self::assertEquals(App::VERSION . '-' . DB_UPDATE_VERSION, $json->software->version);

		self::assertIsArray($json->protocols);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}

	public function testNodeInfo210(): void
	{
		$module = new NodeInfo210(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::config(), []);

		$response = $module->handleRequest(new ServerRequest('GET', 'https://friendica.local/nodeinfo/2.1'));

		self::assertJson((string) $response->getBody());
		self::assertEquals([
			'Content-type'                => ['application/json; charset=utf-8'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());

		$json = json_decode((string) $response->getBody());

		self::assertEquals('1.0', $json->version);

		self::assertEquals('friendica', $json->server->software);
		self::assertEquals(App::VERSION . '-' . DB_UPDATE_VERSION, $json->server->version);

		self::assertIsArray($json->protocols);
		self::assertIsArray($json->services->inbound);
		self::assertIsArray($json->services->outbound);
	}
}
