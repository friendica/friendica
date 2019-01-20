<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Model\Item;
use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserItemDatasetTrait;

class DestroyTest extends ApiTest
{
	use ApiUserItemDatasetTrait;

	/**
	 * Test the api_statuses_destroy() function.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 2);

		$this->mockItemConstants();
		$this->mockItemSelectFirst(Item::ITEM_FIELDLIST, [], [], $item, 1);
		// "false" results in "BadRequestException"
		$this->mockIsResult($item, false, 1);

		api_statuses_destroy('json');
	}

	/**
	 * Test the api_statuses_destroy() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		api_statuses_destroy('json');
	}

	/**
	 * Test the api_statuses_destroy() function with an ID.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testWithId($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 2);
		$this->mockApiStatusShow($item, $user, 1);

		$this->app->argv[3] = 1;
		$result = api_statuses_destroy('json');
		$this->assertStatus($result['status']);
	}
}
