<?php

namespace Friendica\Test\API\Lists;

use Friendica\Test\API\ApiTest;

class StatusesTest extends ApiTest
{
	/**
	 * Test the api_lists_statuses() function.
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 * @return void
	 */
	public function testDefault()
	{
		api_lists_statuses('json');
	}

	/**
	 * Test the api_lists_statuses() function with a list ID.
	 * @return void
	 */
	public function testWithListId()
	{
		$_REQUEST['list_id'] = 1;
		$_REQUEST['page'] = -1;
		$_REQUEST['max_id'] = 10;
		$result = api_lists_statuses('json');
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_lists_statuses() function with a list ID and a RSS result.
	 * @return void
	 */
	public function testWithListIdAndRss()
	{
		$_REQUEST['list_id'] = 1;
		$result = api_lists_statuses('rss');
		$this->assertXml($result, 'statuses');
	}

	/**
	 * Test the api_lists_statuses() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_lists_statuses('json');
	}
}
