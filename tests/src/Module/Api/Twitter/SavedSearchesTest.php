<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\SavedSearches;
use Friendica\Test\ApiTestCase;

class SavedSearchesTest extends ApiTestCase
{
	public function test(): void
	{
		$response = (new SavedSearches(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock);

		$result = $this->toJson($response);

		self::assertEquals(['Content-type' => ['application/json'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());
		self::assertEquals(1, $result[0]->id);
		self::assertEquals(1, $result[0]->id_str);
		self::assertEquals('Saved search', $result[0]->name);
		self::assertEquals('Saved search', $result[0]->query);
	}
}
