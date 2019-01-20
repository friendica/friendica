<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;

class DestroyTest extends ApiTest
{
	/**
	 * Test the api_statuses_destroy() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_statuses_destroy('json');
	}

	/**
	 * Test the api_statuses_destroy() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_statuses_destroy('json');
	}

	/**
	 * Test the api_statuses_destroy() function with an ID.
	 * @return void
	 */
	public function testWithId()
	{
		$this->app->argv[3] = 1;
		$result = api_statuses_destroy('json');
		$this->assertStatus($result['status']);
	}
}
