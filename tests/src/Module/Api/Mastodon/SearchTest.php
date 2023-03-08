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

class SearchTest extends ApiTest
{
	/**
	 * Test the api_search() function.
	 *
	 * @return void
	 */
	public function testApiSearch()
	{
		self::markTestIncomplete('Needs Search to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['q']      = 'reply';
		$_REQUEST['max_id'] = 10;
		$result             = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('reply', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function a count parameter.
	 *
	 * @return void
	 */
	public function testApiSearchWithCount()
	{
		self::markTestIncomplete('Needs Search to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['q']     = 'reply';
		$_REQUEST['count'] = 20;
		$result            = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('reply', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function with an rpp parameter.
	 *
	 * @return void
	 */
	public function testApiSearchWithRpp()
	{
		self::markTestIncomplete('Needs Search to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['q']   = 'reply';
		$_REQUEST['rpp'] = 20;
		$result          = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('reply', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function with an q parameter contains hashtag.
	 * @doesNotPerformAssertions
	 */
	public function testApiSearchWithHashtag()
	{
		self::markTestIncomplete('Needs Search to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['q'] = '%23friendica';
		$result        = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('#friendica', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function with an exclude_replies parameter.
	 * @doesNotPerformAssertions
	 */
	public function testApiSearchWithExcludeReplies()
	{
		self::markTestIncomplete('Needs Search to not set header during call (like at BaseApi::setLinkHeader');

		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['exclude_replies'] = true;
		$_REQUEST['q']               = 'friendica';
		$result                      = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_search() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiSearchWithUnallowedUser()
	{
		self::markTestIncomplete('Needs Search to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_search('json');
	}

	/**
	 * Test the api_search() function without any GET query parameter.
	 *
	 * @return void
	 */
	public function testApiSearchWithoutQuery()
	{
		self::markTestIncomplete('Needs Search to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_search('json');
	}
}
