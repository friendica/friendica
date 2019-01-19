<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserDatasetTrait;

class UserTest extends ApiTest
{
	use ApiUserDatasetTrait;

	/**
	 * Test the api_user() function.
	 * @dataProvider dataApiUser
	 * @return void
	 */
	public function testApiUser($data, $auth)
	{
		$_SESSION = [
			'allow_api' => $auth['allow_api'],
			'authenticated' => $auth['authenticated'],
			'uid' => $data['uid'],
		];

		$this->assertEquals($data['return'], api_user());
	}
}
