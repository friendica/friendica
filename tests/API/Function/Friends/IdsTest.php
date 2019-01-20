<?php

namespace Friendica\Test\API\Friends;

use Friendica\Test\API\ApiTest;

class IdsTest extends ApiTest
{
	/**
	 * Test the api_friends_ids() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_friends_ids('json');
		$this->assertNull($result);
	}
}
