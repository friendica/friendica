<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;

class MentionsTest extends ApiTest
{
	/**
	 * Test the api_statuses_mentions() function.
	 * @return void
	 */
	public function testDefault()
	{
		$this->app->user = ['nickname' => $this->selfUser['nick']];
		$_REQUEST['max_id'] = 10;
		$result = api_statuses_mentions('json');
		$this->assertEmpty($result['status']);
		// We should test with mentions in the database.
	}

	/**
	 * Test the api_statuses_mentions() function with a negative page parameter.
	 * @return void
	 */
	public function testWithNegativePage()
	{
		$_REQUEST['page'] = -2;
		$result = api_statuses_mentions('json');
		$this->assertEmpty($result['status']);
	}

	/**
	 * Test the api_statuses_mentions() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_statuses_mentions('json');
	}

	/**
	 * Test the api_statuses_mentions() function with an RSS result.
	 * @return void
	 */
	public function testWithRss()
	{
		$result = api_statuses_mentions('rss');
		$this->assertXml($result, 'statuses');
	}
}
