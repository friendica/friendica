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

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Model\Contact;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Object\Api\Twitter\User;
use Friendica\Test\FixtureTest;

class ContactEndpointTest extends FixtureTest
{
	public function testIds()
	{
		self::markTestIncomplete('Needs overall refactoring due changed method signature - Calling MrPetovan for help ;-)');

		/*
		$expectedEmpty = [
			'ids' => [],
			'next_cursor' => -1,
			'next_cursor_str' => '-1',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 0,
		];

		self::assertSame($expectedEmpty, ContactEndpointMock::ids(Contact::FOLLOWER, 42));

		$expectedFriend = [
			'ids' => [47],
			'next_cursor' => 0,
			'next_cursor_str' => '0',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 1,
		];

		self::assertSame($expectedFriend, ContactEndpointMock::ids(Contact::FRIEND, 42));
		self::assertSame($expectedFriend, ContactEndpointMock::ids([Contact::FOLLOWER, Contact::FRIEND], 42));

		$result = ContactEndpointMock::ids(Contact::SHARING, 42);

		self::assertArrayHasKey('ids', $result);
		self::assertContainsOnly('int', $result['ids']);
		self::assertSame(45, $result['ids'][0]);

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42);

		self::assertArrayHasKey('ids', $result);
		self::assertContainsOnly('int', $result['ids']);
		self::assertSame(45, $result['ids'][0]);
		*/
	}

	/**
	 * @depends testIds
	 *
	 * @throws NotFoundException
	 */
	public function testIdsStringify()
	{
		self::markTestIncomplete('Needs overall refactoring due changed method signature - Calling MrPetovan for help ;-)');

		/*
		$result = ContactEndpointMock::ids(Contact::SHARING, 42, -1, ContactEndpoint::DEFAULT_COUNT, true);

		self::assertArrayHasKey('ids', $result);
		self::assertContainsOnly('string', $result['ids']);
		self::assertSame('45', $result['ids'][0]);
		*/
	}

	public function testIdsPagination()
	{
		self::markTestIncomplete('Needs overall refactoring due changed method signature - Calling MrPetovan for help ;-)');

		/*
		$expectedDefaultPageResult = [
			'ids' => [45],
			'next_cursor' => 44,
			'next_cursor_str' => '44',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 2,
		];

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42, -1, 1);

		self::assertSame($expectedDefaultPageResult, $result);

		$nextPageCursor = $result['next_cursor'];

		$expectedSecondPageResult = [
			'ids' => [47],
			'next_cursor' => 46,
			'next_cursor_str' => '46',
			'previous_cursor' => -46,
			'previous_cursor_str' => '-46',
			'total_count' => 2,
		];

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42, $nextPageCursor, 1);

		self::assertSame($expectedSecondPageResult, $result);

		$firstPageCursor = $result['previous_cursor'];
		$emptyNextPageCursor = $result['next_cursor'];

		$expectedFirstPageResult = [
			'ids' => [45],
			'next_cursor' => 44,
			'next_cursor_str' => '44',
			'previous_cursor' => -44,
			'previous_cursor_str' => '-44',
			'total_count' => 2,
		];

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42, $firstPageCursor, 1);

		self::assertSame($expectedFirstPageResult, $result);

		$emptyPrevPageCursor = $result['previous_cursor'];

		$expectedEmptyPrevPageResult = [
			'ids' => [],
			'next_cursor' => -1,
			'next_cursor_str' => '-1',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 2,
		];

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42, $emptyPrevPageCursor, 1);

		self::assertSame($expectedEmptyPrevPageResult, $result);

		$expectedEmptyNextPageResult = [
			'ids' => [],
			'next_cursor' => 0,
			'next_cursor_str' => '0',
			'previous_cursor' => -46,
			'previous_cursor_str' => '-46',
			'total_count' => 2,
		];

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42, $emptyNextPageCursor, 1);

		self::assertSame($expectedEmptyNextPageResult, $result);
		*/
	}

	/**
	 * @depends testIds
	 *
	 * @throws NotFoundException
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function testList()
	{
		self::markTestIncomplete('Needs overall refactoring due changed method signature - Calling MrPetovan for help ;-)');

		/*
		$expectedEmpty = [
			'users' => [],
			'next_cursor' => -1,
			'next_cursor_str' => '-1',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 0,
		];

		self::assertSame($expectedEmpty, ContactEndpointMock::list(Contact::FOLLOWER, 42));

		$expectedFriendContactUser = [
			'id' => 45,
			'id_str' => '45',
			'name' => 'Friend contact',
			'screen_name' => 'friendcontact',
			'location' => 'DFRN',
			'derived' => [],
			'url' => 'http://localhost/profile/friendcontact',
			'entities' => [
				'url' => [
					'urls' => [],
				],
				'description' => [
					'urls' => [],
				],
			],
			'description' => '',
			'protected' => false,
			'verified' => true,
			'followers_count' => 0,
			'friends_count' => 0,
			'listed_count' => 0,
			'favourites_count' => 0,
			'statuses_count' => 0,
			'created_at' => 'Fri Feb 02 00:00:00 +0000 0000',
			'profile_banner_url' => 'http://localhost/photo/header/44?ts=-62135596800',
			'profile_image_url_https' => 'http://localhost/photo/contact/48/44?ts=-62135596800',
			'default_profile' => false,
			'default_profile_image' => false,
			'profile_image_url' => 'http://localhost/photo/contact/48/44?ts=-62135596800',
			'profile_image_url_profile_size' => 'http://localhost/photo/contact/80/44?ts=-62135596800',
			'profile_image_url_large' => 'http://localhost/photo/contact/1024/44?ts=-62135596800',
			'utc_offset' => 0,
			'time_zone' => 'UTC',
			'geo_enabled' => false,
			'lang' => NULL,
			'contributors_enabled' => false,
			'is_translator' => false,
			'is_translation_enabled' => false,
			'following' => false,
			'follow_request_sent' => false,
			'statusnet_blocking' => false,
			'notifications' => false,
			'uid' => 42,
			'cid' => 44,
			'pid' => 45,
			'self' => false,
			'network' => 'dfrn',
			'statusnet_profile_url' => 'http://localhost/profile/friendcontact',
		];

		$result = ContactEndpointMock::list(Contact::SHARING, 42);

		self::assertArrayHasKey('users', $result);
		self::assertContainsOnlyInstancesOf(User::class, $result['users']);
		self::assertSame($expectedFriendContactUser, $result['users'][0]->toArray());

		$result = ContactEndpointMock::list([Contact::SHARING, Contact::FRIEND], 42);

		self::assertArrayHasKey('users', $result);
		self::assertContainsOnlyInstancesOf(User::class, $result['users']);
		self::assertSame($expectedFriendContactUser, $result['users'][0]->toArray());
		*/
	}
}
