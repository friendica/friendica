<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Followers;

use Friendica\DI;
use Friendica\Module\Api\Twitter\Followers\Lists;
use Friendica\Test\ApiTestCase;

class ListsTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_f() function.
	 */
	public function testApiStatusesFWithFollowers(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new Lists(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
	}
}
