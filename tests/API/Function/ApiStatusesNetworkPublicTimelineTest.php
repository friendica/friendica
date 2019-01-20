<?php

namespace Friendica\Test\API;

class ApiStatusesNetworkPublicTimelineTest extends ApiTest
{
	/**
	 * Test the api_statuses_networkpublic_timeline() function.
	 * @return void
	 */
	public function testDefault()
	{
		$_REQUEST['max_id'] = 10;
		$result = api_statuses_networkpublic_timeline('json');
		$this->assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_statuses_networkpublic_timeline() function with a negative page parameter.
	 * @return void
	 */
	public function testWithNegativePage()
	{
		$_REQUEST['page'] = -2;
		$result = api_statuses_networkpublic_timeline('json');
		$this->assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_statuses_networkpublic_timeline() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_GET['screen_name'] = 'test';
		api_statuses_networkpublic_timeline('json');
	}

	/**
	 * Test the api_statuses_networkpublic_timeline() function with an RSS result.
	 * @return void
	 */
	public function testWithRss()
	{
		$result = api_statuses_networkpublic_timeline('rss');
		$this->assertXml($result, 'statuses');
	}
}
