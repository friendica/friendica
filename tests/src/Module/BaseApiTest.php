<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module;

use Friendica\Module\BaseApi;
use Friendica\Test\ApiTestCase;

class BaseApiTest extends ApiTestCase
{
	public function testWithWrongAuth(): void
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'auth'   => true
		];
		$_SESSION['authenticated'] = false;
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path';

		$args = DI::args()->determine($_SERVER, $_GET);

		self::assertEquals(
			'{"status":{"error":"This API requires login","code":"401 Unauthorized","request":"api_path"}}',
			api_call($this->app, $args)
		);
		*/
	}

	/**
	 * Test the BaseApi::getCurrentUserID() function.
	 *
	 * @return void
	 */
	public function testApiUser(): void
	{
		self::assertEquals(parent::SELF_USER['id'], BaseApi::getCurrentUserID());
	}
}
