<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;

class ShowTest extends ApiTest
{
	/**
	 * Test the api_statuses_show() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_statuses_show('json');
	}

	/**
	 * Test the api_statuses_show() function with an ID.
	 * @return void
	 */
	public function testWithId()
	{
		$this->app->argv[3] = 1;
		$result = api_statuses_show('json');
		$this->assertStatus($result['status']);
	}

	/**
	 * Test the api_statuses_show() function with the conversation parameter.
	 * @return void
	 */
	public function testWithConversation()
	{
		$this->app->argv[3] = 1;
		$_REQUEST['conversation'] = 1;
		$result = api_statuses_show('json');
		$this->assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_statuses_show() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_statuses_show('json');
	}
}
