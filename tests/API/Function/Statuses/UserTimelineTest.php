<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;

class UserTimelineTest extends ApiTest
{
	/**
	 * Test the api_statuses_user_timeline() function.
	 * @return void
	 */
	public function testDefault()
	{
		$_REQUEST['max_id'] = 10;
		$_REQUEST['exclude_replies'] = true;
		$_REQUEST['conversation_id'] = 1;
		$result = api_statuses_user_timeline('json');
		$this->assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_statuses_user_timeline() function with a negative page parameter.
	 * @return void
	 */
	public function testWithNegativePage()
	{
		$_REQUEST['page'] = -2;
		$result = api_statuses_user_timeline('json');
		$this->assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_statuses_user_timeline() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_statuses_user_timeline('json');
	}

	/**
	 * Test the api_statuses_user_timeline() function with an RSS result.
	 * @return void
	 */
	public function testWithRss()
	{
		$result = api_statuses_user_timeline('rss');
		$this->assertXml($result, 'statuses');
	}
}
