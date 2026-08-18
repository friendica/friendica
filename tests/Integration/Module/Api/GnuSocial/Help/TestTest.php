<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\GnuSocial\Help;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\Help\Test;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class TestTest extends ApiTestCase
{
	public function testJson(): void
	{
		$module = new Test(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);

		$request = new ServerRequest('GET', 'https://friendica.local/api/gnusocial/help/test');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('ok', $json);
	}

	public function testXml(): void
	{
		$module = new Test(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']);

		$request = new ServerRequest('GET', 'https://friendica.local/api/gnusocial/help/test');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertXml((string) $response->getBody(), 'ok');
	}
}
