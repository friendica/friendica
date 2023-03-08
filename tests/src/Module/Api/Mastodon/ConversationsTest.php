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

namespace Friendica\Test\src\Module\Api\Mastodon;

use Friendica\Test\src\Module\Api\ApiTest;

class ConversationsTest extends ApiTest
{
	/**
	 * Test the api_conversation_show() function.
	 *
	 * @return void
	 */
	public function testApiConversationShow()
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_conversation_show('json');
	}

	/**
	 * Test the api_conversation_show() function with an ID.
	 *
	 * @return void
	 */
	public function testApiConversationShowWithId()
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		/*
		DI::args()->setArgv(['', '', '', 1]);
		$_REQUEST['max_id'] = 10;
		$_REQUEST['page']   = -2;
		$result             = api_conversation_show('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_conversation_show() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiConversationShowWithUnallowedUser()
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_conversation_show('json');
	}
}
