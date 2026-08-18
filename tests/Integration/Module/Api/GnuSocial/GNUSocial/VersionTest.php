<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\GnuSocial\GNUSocial;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\GNUSocial\Version;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class VersionTest extends ApiTestCase
{
	public function test(): void
	{
		$module = new Version(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);

		$request = new ServerRequest('GET', 'https://friendica.local/api/gnusocial/version');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertEquals('"0.9.7"', $response->getBody());
	}
}
