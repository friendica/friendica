<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Friends;

use Friendica\DI;
use Friendica\Module\Api\Twitter\Friends\Lists;
use Friendica\Test\ApiTestCase;

class ListsTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_f() function.
	 *
	 * @return void
	 */
	public function testApiStatusesFWithFriends(): void
	{
		$response = (new Lists(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
	}

	/**
	 * Test the api_statuses_f() function an undefined cursor GET variable.
	 *
	 */
	public function testApiStatusesFWithUndefinedCursor(): void
	{
		self::markTestIncomplete('Needs refactoring of Lists - replace filter_input() with $request parameter checks');

		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_statuses_f('friends'));
	}
}
