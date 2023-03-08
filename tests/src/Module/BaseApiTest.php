<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Module;

use Friendica\Module\BaseApi;
use Friendica\Test\src\Module\Api\ApiTest;

class BaseApiTest extends ApiTest
{
	public function testWithWrongAuth()
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
	public function testApiUser()
	{
		self::assertEquals(parent::SELF_USER['id'], BaseApi::getCurrentUserID());
	}
}
