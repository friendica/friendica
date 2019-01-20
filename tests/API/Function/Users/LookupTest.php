<?php

namespace Friendica\Test\API\Users;

use Friendica\Test\API\ApiTest;

class LookupTest extends ApiTest
{
	/**
	 * Test the api_users_lookup() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\NotFoundException
	 */
	public function testDefault()
	{
		api_users_lookup('json');
	}

	/**
	 * Test the api_users_lookup() function with an user ID.
	 * @dataProvider dataApiUser
	 * @return void
	 */
	public function testWithUserId($data)
	{
		$_REQUEST['user_id'] = $data['uid'];
		$result = api_users_lookup('json');
		$this->assertOtherUser($result['users'][0], $data);
	}
}
