<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;

class ApiStatusesUpdate extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use L10nMockTrait;

	/**
	 * Test the api_statuses_update() function with an HTML status.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testWithHtml($user, $item)
	{
		$this->mockL10nT();

		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 2);

		// Return something below 'throttle_limit_day'
		$this->mockCount('thread', \Mockery::any(), 1, 3);

		/// Mocking this select to return "false" forces the item_post() function to return 0
		$this->mockSelectFirst('user', [], ['uid' => $user['uid']], false, 1);
		$this->mockIsResult(false, false, 1);

		$this->mockApiStatusShow($item, 1);

		$_GET['htmlstatus'] = '<b>Status content</b>';

		$result = api_statuses_update('json');
		$this->assertStatus($result['status']);
	}

	/**
	 * Test the api_statuses_update() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_statuses_update('json');
	}

	/**
	 * Test the api_statuses_update() function with a parent status.
	 * @return void
	 */
	public function testWithParent()
	{
		$this->markTestIncomplete('This triggers an exit() somewhere and kills PHPUnit.');
	}

	/**
	 * Test the api_statuses_update() function with a media_ids parameter.
	 * @return void
	 */
	public function testWithMediaIds()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_statuses_update() function with the throttle limit reached.
	 * @return void
	 */
	public function testWithDayThrottleReached()
	{
		$this->markTestIncomplete();
	}
}
