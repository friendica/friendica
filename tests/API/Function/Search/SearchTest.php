<?php

namespace Friendica\Test\API\Search;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;

class SearchTest extends ApiTest
{
	use ApiUserDatasetTrait;
	use L10nMockTrait;

	/**
	 * Test the api_search() function.
	 * @return void
	 */
	public function testDefault()
	{
		$_REQUEST['q'] = 'reply';
		$_REQUEST['max_id'] = 10;
		$result = api_search('json');
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
			$this->assertContains('reply', $status['text'], null, true);
		}
	}

	/**
	 * Test the api_search() function a count parameter.
	 * @return void
	 */
	public function testWithCount()
	{
		$_REQUEST['q'] = 'reply';
		$_REQUEST['count'] = 20;
		$result = api_search('json');
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
			$this->assertContains('reply', $status['text'], null, true);
		}
	}

	/**
	 * Test the api_search() function with an rpp parameter.
	 * @return void
	 */
	public function testWithRpp()
	{
		$_REQUEST['q'] = 'reply';
		$_REQUEST['rpp'] = 20;
		$result = api_search('json');
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
			$this->assertContains('reply', $status['text'], null, true);
		}
	}


	/**
	 * Test the api_search() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_search('json');
	}

	/**
	 * Test the api_search() function without any GET query parameter.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithoutQuery()
	{
		api_search('json');
	}

	/**
	 * Test the api_search() function with an q parameter contains hashtag.
	 * @return void
	 */
	public function testWithHashtag()
	{
		$_REQUEST['q'] = '%23friendica';
		$result = api_search('json');
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
			$this->assertContains('#friendica', $status['text'], null, true);
		}
	}
}
