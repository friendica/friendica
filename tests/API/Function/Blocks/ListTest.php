<?php

namespace Friendica\Test\API\Blocks;

use Friendica\Test\API\ApiTest;

class ListTest extends ApiTest
{
	/**
	 * Test the api_conversation_show() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_conversation_show('json');
	}

	/**
	 * Test the api_conversation_show() function with an ID.
	 * @return void
	 */
	public function testWithId()
	{
		$this->app->argv[3] = 1;
		$_REQUEST['max_id'] = 10;
		$_REQUEST['page'] = -2;
		$result = api_conversation_show('json');
		$this->assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_conversation_show() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_conversation_show('json');
	}
}
