<?php

namespace Friendica\Test\API\Friendica\Photos;

use Friendica\Test\API\ApiTest;

class ListTest extends ApiTest
{
	/**
	 * Test the api_fr_photos_list() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_fr_photos_list('json');
		$this->assertArrayHasKey('photo', $result);
	}

	/**
	 * Test the api_fr_photos_list() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		api_fr_photos_list('json');
	}
}
