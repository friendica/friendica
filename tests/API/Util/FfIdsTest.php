<?php

namespace Friendica\Test\API;

class FfIdsTest extends ApiTest
{
	/**
	 * Test the api_ff_ids() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_ff_ids('json');
		$this->assertNull($result);
	}

	/**
	 * Test the api_ff_ids() function with a result.
	 * @return void
	 */
	public function testWithResult()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_ff_ids() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_ff_ids('json');
	}
}
