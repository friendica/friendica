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

namespace Friendica\Test\src\Module\Api\Mastodon\Accounts;

use Friendica\Test\src\Module\Api\ApiTest;

class StatusesTest extends ApiTest
{
	/**
	 * Test the api_status_show() function.
	 */
	public function testApiStatusShowWithJson()
	{
		self::markTestIncomplete('Needs Statuses to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_status_show('json', 1);
		// self::assertStatus($result['status']);
	}

	/**
	 * Test the api_status_show() function with an XML result.
	 */
	public function testApiStatusShowWithXml()
	{
		self::markTestIncomplete('Needs Statuses to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_status_show('xml', 1);
		// self::assertXml($result, 'statuses');
	}
}
