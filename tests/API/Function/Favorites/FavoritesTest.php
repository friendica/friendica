<?php

namespace Friendica\Test\API\Favorites;

use Friendica\Test\API\ApiTest;

class FavoritesTest extends ApiTest
{
	/**
	 * Test the api_favorites() function.
	 * @return void
	 */
	public function testDefault()
	{
		$_REQUEST['page'] = -1;
		$_REQUEST['max_id'] = 10;
		$result = api_favorites('json');
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_favorites() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_favorites('json');
	}

	/**
	 * Test the api_favorites() function with an RSS result.
	 * @return void
	 */
	public function testWithRss()
	{
		$result = api_favorites('rss');
		$this->assertXml($result, 'statuses');
	}
}
