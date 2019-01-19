<?php

namespace Friendica\Test\API\Account;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserItemDatasetTrait;

/**
 * Tests for api_account_update_profile()
 * @see api_account_update_profile()
 */
class UpdateProfileTest extends ApiTest
{
	use ApiUserItemDatasetTrait;

	/**
	 * Test the api_account_update_profile() function.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testDefault($user, $item)
	{
		$new_name = 'new_name';
		$new_desc = 'new_description';

		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, 1);

		// Mocking the NewName Update
		$this->mockUpdate('profile', ['name' => $new_name], ['uid' => $user['uid']], [], true, 1);
		$this->mockUpdate('user', ['username' => $new_name], ['uid' => $user['uid']], [], true, 1);
		$this->mockUpdate('contact', ['name' => $new_name], ['uid' => $user['uid'], 'self' => 1], [], true, 1);
		$this->mockUpdate('contact', ['name' => $new_name], ['id' => $user['id']], [], true, 1);

		// Mocking the NewDescription Update
		$this->mockUpdate('profile', ['about' => $new_desc], ['uid' => $user['uid']], [], true, 1);
		$this->mockUpdate('contact', ['about' => $new_desc], ['uid' => $user['uid']], [], true, 1);
		$this->mockUpdate('contact', ['about' => $new_desc], ['id' => $user['id']], [], true, 1);

		$_POST['name'] = 'new_name';
		$_POST['description'] = 'new_description';
		$result = api_account_update_profile('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		$this->assertEquals($user['id'], $result['user']['cid']);
		$this->assertEquals($user['location'], $result['user']['location']);
		$this->assertEquals($user['nick'], $result['user']['screen_name']);
		$this->assertEquals($user['network'], $result['user']['network']);
		$this->assertEquals('new_name', $result['user']['name']);
		$this->assertEquals('new_description', $result['user']['description']);
	}
}
