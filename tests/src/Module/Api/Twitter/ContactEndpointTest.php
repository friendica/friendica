<?php

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Model\Contact;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Domain\Entity\Api\Twitter\User;
use Friendica\Test\FixtureTest;

class ContactEndpointTest extends FixtureTest
{
	public function testGetUid()
	{
		$this->assertSame(42, ContactEndpointMock::getUid(42));
		$this->assertSame(42, ContactEndpointMock::getUid(null, 'selfcontact'));
		$this->assertSame(42, ContactEndpointMock::getUid(84, 'selfcontact'));
	}

	public function testGetUidContactIdNotFound()
	{
		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('Contact not found');

		ContactEndpointMock::getUid(84);
	}

	public function testGetUidScreenNameNotFound()
	{
		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('User not found');

		ContactEndpointMock::getUid(null, 'othercontact');
	}

	public function testGetUidContactIdScreenNameNotFound()
	{
		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('User not found');

		ContactEndpointMock::getUid(42, 'othercontact');
	}

	public function testIds()
	{
		$expectedEmpty = [
			'ids' => [],
			'next_cursor' => -1,
			'next_cursor_str' => '-1',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 0,
		];

		$this->assertSame($expectedEmpty, ContactEndpointMock::ids(Contact::FOLLOWER, 42));

		$expectedFriend = [
			'ids' => [47],
			'next_cursor' => 0,
			'next_cursor_str' => '0',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 1,
		];

		$this->assertSame($expectedFriend, ContactEndpointMock::ids(Contact::FRIEND, 42));
		$this->assertSame($expectedFriend, ContactEndpointMock::ids([Contact::FOLLOWER, Contact::FRIEND], 42));

		$result = ContactEndpointMock::ids(Contact::SHARING, 42);

		$this->assertArrayHasKey('ids', $result);
		$this->assertContainsOnly('int', $result['ids']);
		$this->assertSame(45, $result['ids'][0]);

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42);

		$this->assertArrayHasKey('ids', $result);
		$this->assertContainsOnly('int', $result['ids']);
		$this->assertSame(45, $result['ids'][0]);
	}

	/**
	 * @depends testIds
	 *
	 * @throws NotFoundException
	 */
	public function testIdsStringify()
	{
		$result = ContactEndpointMock::ids(Contact::SHARING, 42, -1, ContactEndpoint::DEFAULT_COUNT, true);

		$this->assertArrayHasKey('ids', $result);
		$this->assertContainsOnly('string', $result['ids']);
		$this->assertSame('45', $result['ids'][0]);
	}

	public function testIdsPagination()
	{
		$expectedDefaultPageResult = [
			'ids' => [45],
			'next_cursor' => 44,
			'next_cursor_str' => '44',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 2,
		];

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42, -1, 1);

		$this->assertSame($expectedDefaultPageResult, $result);

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

		$this->assertSame($expectedSecondPageResult, $result);

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

		$this->assertSame($expectedFirstPageResult, $result);

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

		$this->assertSame($expectedEmptyPrevPageResult, $result);

		$expectedEmptyNextPageResult = [
			'ids' => [],
			'next_cursor' => 0,
			'next_cursor_str' => '0',
			'previous_cursor' => -46,
			'previous_cursor_str' => '-46',
			'total_count' => 2,
		];

		$result = ContactEndpointMock::ids([Contact::SHARING, Contact::FRIEND], 42, $emptyNextPageCursor, 1);

		$this->assertSame($expectedEmptyNextPageResult, $result);
	}

	/**
	 * @depends testIds
	 *
	 * @throws NotFoundException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function testList()
	{
		$expectedEmpty = [
			'users' => [],
			'next_cursor' => -1,
			'next_cursor_str' => '-1',
			'previous_cursor' => 0,
			'previous_cursor_str' => '0',
			'total_count' => 0,
		];

		$this->assertSame($expectedEmpty, ContactEndpointMock::list(Contact::FOLLOWER, 42));

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
			'verified' => false,
			'followers_count' => 0,
			'friends_count' => 0,
			'listed_count' => 0,
			'favourites_count' => 0,
			'statuses_count' => 0,
			'created_at' => 'Fri Feb 02 00:00:00 +0000 0000',
			'profile_banner_url' => '',
			'profile_image_url_https' => '',
			'default_profile' => false,
			'default_profile_image' => false,
			'profile_image_url' => '',
			'profile_image_url_profile_size' => '',
			'profile_image_url_large' => '',
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
			'self' => 0,
			'network' => 'dfrn',
			'statusnet_profile_url' => 'http://localhost/profile/friendcontact',
		];

		$result = ContactEndpointMock::list(Contact::SHARING, 42);

		$this->assertArrayHasKey('users', $result);
		$this->assertContainsOnlyInstancesOf(User::class, $result['users']);
		$this->assertSame($expectedFriendContactUser, $result['users'][0]->toArray());

		$result = ContactEndpointMock::list([Contact::SHARING, Contact::FRIEND], 42);

		$this->assertArrayHasKey('users', $result);
		$this->assertContainsOnlyInstancesOf(User::class, $result['users']);
		$this->assertSame($expectedFriendContactUser, $result['users'][0]->toArray());
	}
}
