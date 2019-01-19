<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;

class RepeatTest extends ApiTest
{
	/**
	 * Test the api_statuses_repeat() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testDefault()
	{
		api_statuses_repeat('json');
	}

	/**
	 * Test the api_statuses_repeat() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_statuses_repeat('json');
	}

	/**
	 * Test the api_statuses_repeat() function with an ID.
	 * @return void
	 */
	public function testWithId()
	{
		$this->app->argv[3] = 1;
		$result = api_statuses_repeat('json');
		$this->assertStatus($result['status']);

		// Also test with a shared status
		$this->app->argv[3] = 5;
		$result = api_statuses_repeat('json');
		$this->assertStatus($result['status']);
	}
}
