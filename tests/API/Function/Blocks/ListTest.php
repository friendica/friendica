<?php

namespace Friendica\Test\API\Blocks;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserDatasetTrait;

class ListTest extends ApiTest
{
	use ApiUserDatasetTrait;

	/**
	 * Test the api_blocks_list() function.
	 * @return void
	 * @dataProvider dataApiUserFull
	 */
	public function testDefault($user)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, true, 1);
		$this->mockDBAQ(\Mockery::any(), [['nurl' => $user['url']]], \Mockery::any(), 1);
		$this->mockApiGetUser($user, $user['url'], true, 1);

		$result = api_blocks_list('json');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_blocks_list() function an undefined cursor GET variable.
	 * @return void
	 * @dataProvider dataApiUserFull
	 */
	public function testWithUndefinedCursor($user)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, true, 1);

		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_blocks_list('json'));
	}
}
