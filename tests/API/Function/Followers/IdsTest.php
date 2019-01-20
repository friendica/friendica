<?php

namespace Friendica\Test\API\Followers;

use Friendica\Test\API\ApiTest;

class IdsTest extends ApiTest
{
	/**
	 * Test the api_followers_ids() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_followers_ids('json');
		$this->assertNull($result);
	}
}
