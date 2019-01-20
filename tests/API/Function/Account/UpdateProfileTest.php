<?php

namespace Friendica\Test\API\Account;

use Friendica\Test\API\ApiTest;

class UpdateProfileTest extends ApiTest
{
	/**
	 * Test the api_account_update_profile() function.
	 * @return void
	 */
	public function testDefault()
	{
		$_POST['name'] = 'new_name';
		$_POST['description'] = 'new_description';
		$result = api_account_update_profile('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		$this->assertEquals($this->selfUser['id'], $result['user']['cid']);
		$this->assertEquals('DFRN', $result['user']['location']);
		$this->assertEquals($this->selfUser['nick'], $result['user']['screen_name']);
		$this->assertEquals('dfrn', $result['user']['network']);
		$this->assertEquals('new_name', $result['user']['name']);
		$this->assertEquals('new_description', $result['user']['description']);
	}
}
