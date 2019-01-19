<?php

namespace Friendica\Test\API\Lists;

use Friendica\Test\API\ApiTest;

class OwnershipsTest extends ApiTest
{
	/**
	 * Test the api_lists_ownerships() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_lists_ownerships('json');
		foreach ($result['lists']['lists'] as $list) {
			$this->assertList($list);
		}
	}

	/**
	 * Test the api_lists_ownerships() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_lists_ownerships('json');
	}
}
