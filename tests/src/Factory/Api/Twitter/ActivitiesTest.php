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

namespace Friendica\Test\src\Factory\Api\Twitter;

use Friendica\DI;
use Friendica\Factory\Api\Friendica\Activities;
use Friendica\Test\FixtureTest;

class ActivitiesTest extends FixtureTest
{
	/**
	 * Test the api_format_items_activities() function.
	 *
	 * @return void
	 */
	public function testApiFormatItemsActivities()
	{
		$item = ['uid' => 0, 'uri-id' => 1];

		$result = (new Activities(DI::logger(), DI::twitterUser()))
			->createFromUriId($item['uri-id'], $item['uid']);

		self::assertArrayHasKey('like', $result);
		self::assertArrayHasKey('dislike', $result);
		self::assertArrayHasKey('attendyes', $result);
		self::assertArrayHasKey('attendno', $result);
		self::assertArrayHasKey('attendmaybe', $result);
	}

	/**
	 * Test the api_format_items_activities() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiFormatItemsActivitiesWithXml()
	{
		$item = ['uid' => 0, 'uri-id' => 1];

		$result = (new Activities(DI::logger(), DI::twitterUser()))
			->createFromUriId($item['uri-id'], $item['uid'], 'xml');

		self::assertArrayHasKey('friendica:like', $result);
		self::assertArrayHasKey('friendica:dislike', $result);
		self::assertArrayHasKey('friendica:attendyes', $result);
		self::assertArrayHasKey('friendica:attendno', $result);
		self::assertArrayHasKey('friendica:attendmaybe', $result);
	}
}
