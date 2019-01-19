<?php

namespace Friendica\Test\API;

class UniqueIdToNurlTest extends ApiTest
{
	private $users = [
		'self' => [
			'uid' => 42,
			'return' => [
				'nurl' => 'http://bla',
			],
		],
		'friend' => [
			'uid' => 43,
			'return' => [
				'nurl' => 'http://bla',
			],
		],
		'other' => [
			'uid' => 44,
			'return' => false,
		],
		'wrong' => [
			'uid' => null,
			'return' => false,
		]
	];

	public function dataApiUser()
	{
		return $this->users;
	}

	/**
	 * Test the api_unique_id_to_nurl() function.
	 * @dataProvider dataApiUser
	 * @return void
	 */
	public function testApiUniqueIdToNurl($uid, $return)
	{
		$this->mockSelectFirst('contact', ['nurl'], ['id' => $uid], $return, 1);
		$this->mockIsResult($return, $return !== false, 1);
		if (!$return) {
			$this->assertFalse(api_unique_id_to_nurl($uid));
		} else {
			$this->assertSame($return['nurl'], api_unique_id_to_nurl($uid));
		}
	}
}
