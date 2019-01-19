<?php

namespace Friendica\Test\Api;

use Friendica\Test\ApiTest;

class ApiItemGetUserTest extends ApiTest
{
	/**
	 * Test the api_item_get_user() function.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testApiItemGetUser($data)
	{
		$this->mockLogin($data['uid']);

		$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = " . $data['uid'] . " AND `contact`.`self` ";

		$this->mockP($stmt, [$data], 2);
		$this->mockIsResult([$data], true, 2);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 2);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 2);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 2);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 2);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 4);
		$this->mockConstants();

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
		$this->mockLogin($data['uid']);

		$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = " . $data['uid'] . " AND `contact`.`self` ";

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		$users = api_item_get_user($this->app, ['thr-parent' => 'item_parent', 'uri' => 'item_uri']);
		$this->assertUser($users[0], $data);
		$this->assertEquals($users[0], $users[1]);
	}
}
