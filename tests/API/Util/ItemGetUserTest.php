<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserDatasetTrait;

class ItemGetUserTest extends ApiTest
{
	use ApiUserDatasetTrait;

	/**
	 * Test the api_item_get_user() function.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testApiItemGetUser($data)
	{
		$this->mockApiUser($data['uid']);
		$this->mockApiGetUser($data, 2);

		$users = api_item_get_user($this->app, []);
		$this->assertUser($users[0], $data);
	}

	/**
	 * Test the api_item_get_user() function with a different item parent.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testApiItemGetUserWithDifferentParent($data)
	{
		$this->mockApiUser($data['uid']);
		$this->mockApiGetUser($data);

		$users = api_item_get_user($this->app, ['thr-parent' => 'item_parent', 'uri' => 'item_uri']);
		$this->assertUser($users[0], $data);
		$this->assertEquals($users[0], $users[1]);
	}
}
