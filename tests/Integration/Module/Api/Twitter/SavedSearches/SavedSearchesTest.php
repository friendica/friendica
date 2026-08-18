<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\SavedSearches;

use Friendica\DI;
use Friendica\Module\Api\Twitter\SavedSearches;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class SavedSearchesTest extends ApiTestCase
{
	public function testApiSavedSearches(): void
	{
		$module = new SavedSearches(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);

		$request = (new ServerRequest('GET', 'https://friendica.local/api/saved_searches'))
			->withQueryParams(['format' => 'json']);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertNotEmpty($json);
		self::assertEquals(1, $json[0]->id);
		self::assertEquals('Saved search', $json[0]->name);
		self::assertEquals('Saved search', $json[0]->query);
	}
}
