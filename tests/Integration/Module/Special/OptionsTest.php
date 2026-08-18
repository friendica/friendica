<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Special;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Special\Options;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class OptionsTest extends ApiTestCase
{
	public function testOptionsAll(): void
	{
		$this->useHttpMethod(Router::OPTIONS);

		$module = new Options(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$response = $module->handleRequest(new ServerRequest('OPTIONS', 'https://friendica.local/api/v1/statuses'));

		self::assertEmpty((string) $response->getBody());
		self::assertEquals(204, $response->getStatusCode());
		self::assertEquals('No Content', $response->getReasonPhrase());
		self::assertEquals(implode(',', Router::ALLOWED_METHODS), $response->getHeaderLine('Allow'));
	}

	public function testOptionsSpecific(): void
	{
		$this->useHttpMethod(Router::OPTIONS);

		$module = new Options(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'AllowedMethods' => [Router::GET, Router::POST],
		]);

		$response = $module->handleRequest(new ServerRequest('OPTIONS', 'https://friendica.local/api/v1/statuses'));

		self::assertEmpty((string) $response->getBody());
		self::assertEquals(204, $response->getStatusCode());
		self::assertEquals('No Content', $response->getReasonPhrase());
		self::assertEquals(implode(',', [Router::GET, Router::POST]), $response->getHeaderLine('Allow'));
	}
}
