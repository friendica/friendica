<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;

class FollowersTest extends ApiTest
{
	/**
	 * Test the api_statuses_followers() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_statuses_followers('json');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_followers() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_statuses_followers('json'));
	}
}
