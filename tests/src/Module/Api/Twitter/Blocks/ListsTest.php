<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Blocks;

use Friendica\DI;
use Friendica\Module\Api\Twitter\Blocks\Lists;
use Friendica\Test\ApiTestCase;

class ListsTest extends ApiTestCase
{
	/**
	 * Test the api_statuses_f() function.
	 */
	public function testApiStatusesFWithBlocks(): void
	{
		$response = (new Lists(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [])) // @phpstan-ignore method.deprecated
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
	}

	/**
	 * Test the api_blocks_list() function an undefined cursor GET variable.
	 *
	 */
	public function testApiBlocksListWithUndefinedCursor(): void
	{
		self::markTestIncomplete('Needs refactoring of Lists - replace filter_input() with $request parameter checks');

		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_blocks_list('json'));
	}

	public function testHandleRequestBlocksListsReturnsBlockedIds(): void
	{
		$module = new Lists(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn([]);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
	}
}
