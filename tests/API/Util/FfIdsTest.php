<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserDatasetTrait;

class FfIdsTest extends ApiTest
{
	use ApiUserDatasetTrait;

	/**
	 * Test the api_ff_ids() function.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testDefault($user)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, 1);
		$stmt = "SELECT `pcontact`.`id` FROM `contact`
			INNER JOIN `contact` AS `pcontact` ON `contact`.`nurl` = `pcontact`.`nurl` AND `pcontact`.`uid` = 0
			WHERE `contact`.`uid` = %s AND NOT `contact`.`self`";
		$this->mockDBAQ($stmt, [['id' => $user['id']]], $user['uid'], 1);
		$this->mockIsResult([['id' => $user['id']]], false, 1);

		$result = api_ff_ids('json');
		$this->assertNull($result);
	}

	/**
	 * Test the api_ff_ids() function with a result.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithResult($user)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, 1);
		$stmt = "SELECT `pcontact`.`id` FROM `contact`
			INNER JOIN `contact` AS `pcontact` ON `contact`.`nurl` = `pcontact`.`nurl` AND `pcontact`.`uid` = 0
			WHERE `contact`.`uid` = %s AND NOT `contact`.`self`";
		$this->mockDBAQ($stmt, [['id' => $user['id']]], $user['uid'], 1);
		$this->mockIsResult([['id' => $user['id']]], true, 1);

		$result = api_ff_ids('json');
		$this->assertEquals(['id' => [$user['id']]], $result);
	}

	/**
	 * Test the api_ff_ids() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		api_ff_ids('json');
	}
}
