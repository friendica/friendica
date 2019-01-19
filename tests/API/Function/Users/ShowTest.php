<?php

namespace Friendica\Test\API\Users;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;

class ShowTest extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use L10nMockTrait;

	/**
	 * Test the api_users_show() function.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testDefault($user, $item)
	{
		$this->mockL10nT();

		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 1);
		$this->mockApiUsersShow($item, 1);

		$result = api_users_show('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		$this->assertEquals($user['cid'], $result['user']['cid']);
		$this->assertEquals($user['location'], $result['user']['location']);
		$this->assertEquals($user['name'], $result['user']['name']);
		$this->assertEquals($user['nick'], $result['user']['screen_name']);
		$this->assertEquals($user['location'], $result['user']['network']);
		$this->assertTrue($result['user']['verified']);
	}

	/**
	 * Test the api_users_show() function with an XML result.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testWithXml($user, $item)
	{
		$this->mockL10nT();

		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 1);
		$this->mockApiUsersShow($item, 1);

		$result = api_users_show('xml');
		$this->assertXml($result, 'statuses');
	}
}
