<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Mastodon\Accounts;

use Friendica\Test\ApiTestCase;

class StatusesTest extends ApiTestCase
{
	/**
	 * Test the api_status_show() function.
	 */
	public function testApiStatusShowWithJson(): void
	{
		self::markTestIncomplete('Needs Statuses to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_status_show('json', 1);
		// self::assertStatus($result['status']);
	}

	/**
	 * Test the api_status_show() function with an XML result.
	 */
	public function testApiStatusShowWithXml(): void
	{
		self::markTestIncomplete('Needs Statuses to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_status_show('xml', 1);
		// self::assertXml($result, 'statuses');
	}
}
