<?php

namespace Friendica\Test\Api;

use Friendica\Test\ApiTest;

class ApiUserTest extends ApiTest
{
	private $users = [
		'self' => [
			'data' => [
				'uid' => 42,
				'return' => 42,
			],
			'auth' => [
				'allow_api' => true,
				'authenticated' => true,
			],
		],
		'friend' => [
			'data' => [
				'uid' => 43,
				'return' => 43,
			],
			'auth' => [
				'allow_api' => true,
				'authenticated' => true,
			],
		],
		'other' => [
			'data' => [
				'uid' => 44,
				'return' => false,
			],
			'auth' => [
				'allow_api' => true,
				'authenticated' => false,
			],
		],
		'wrong' => [
			'data' => [
				'uid' => null,
				'return' => false,
			],
			'auth' => [
				'allow_api' => false,
				'authenticated' => false,
			],
		]
	];

	public function dataApiUser()
	{
		return $this->users;
	}

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
