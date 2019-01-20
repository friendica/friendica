<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;

class FriendsTest extends ApiTest
{
	/**
	 * Test the api_statuses_friends() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_statuses_friends('json');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_friends() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_statuses_friends('json'));
	}
}
