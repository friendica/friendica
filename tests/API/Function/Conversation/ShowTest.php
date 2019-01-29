<?php

namespace Friendica\Test\API\Conversation;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserItemDatasetTrait;

class ShowTest extends ApiTest
{
	use ApiUserItemDatasetTrait;

	/**
	 * Test the api_conversation_show() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @dataProvider dataApiUserItemFull
	 */
	public function testDefault($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, null, isset($user['self']), true, 1);

		$this->mockItemSelectFirst(['parent-uri'], ['id' => 0], [], null, 1);
		$this->mockIsResult(null, false, 1);

		api_conversation_show('json');
	}

	/**
	 * Test the api_conversation_show() function with an ID.
	 * @return void
	 * @dataProvider dataApiUserItemFull
	 */
	public function testWithId($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, null, isset($user['self']), true, 1);

		$this->mockItemSelectFirst(['parent-uri'], ['id' => $item['uid']], [], ['parent-uri' => $item['parent-uri']], 1);
		$this->mockIsResult(['parent-uri' => $item['parent-uri']], true, 1);

		$this->mockItemSelectFirst(['id'], ['uri' => $item['parent-uri'], 'uid' => [0, $user['uid']]], ['order' => ['uid' => true]], ['id' => $item['id']], 1);
		$this->mockIsResult(['id' => $item['id']], true, 1);

		$this->app->argv[3] = $item['uid'];
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
	 * @dataProvider dataApiUserItemFull
	 * @runInSeparateProcess
	 */
	public function testWithUnallowedUser($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, $user['nick'], isset($user['self']), false,1);

		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $user['nick'];
		api_conversation_show('json');
	}
}
