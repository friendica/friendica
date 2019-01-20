<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;
use Friendica\Test\Util\Mocks\PhotoMockTrait;

class ApiUsersShowTest extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use L10nMockTrait;
	use PhotoMockTrait;

	/**
	 * Test the api_statuses_mediap() function.
	 * @dataProvider dataApiUserMediaFull
	 * @return void
	 */
	public function testDefault($user, $media)
	{
		$this->mockL10nT();

		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 3);

		$this->mockEscape($user['nick'], 1);
		$stmt = "SELECT `user`.*, `contact`.`id` FROM `user`
				INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
				WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0
				AND `contact`.`self` = 1 LIMIT 1";
		$this->mockDBAQ($stmt, [$user], $user['nick'], 1);

		$this->mockConfigGet('system', 'maximagesize', false, 1);
		$this->mockConfigGet('system', 'png_quality', false, 1);
		$this->mockConfigGet('system', 'max_image_length', false, 1);

		$this->mockPhotoNewResource(123, 1);
		$this->mockPhotoStore(true, 1);

		$stmt = "SELECT `id`, `datasize`, `width`, `height`, `type` FROM `photo`
			WHERE `resource-id` = '%s'
			ORDER BY `width` DESC LIMIT 1";
		$this->mockDBAQ($stmt, [$media], 123, 1);

		/// Mocking this select to return "false" forces the item_post() function to return 0
		$this->mockSelectFirst('user', [], ['uid' => $user['uid']], false, 1);
		$this->mockIsResult(false, false, 1);

		$this->mockApiStatusShow($media, 1);

		$this->app->argc = 2;

		$_FILES = [
			'media' => $media,
		];

		$_GET['status'] = '<b>Status content</b>';

		$result = api_statuses_mediap('json');
		$this->assertStatus($result['status']);
	}

	/**
	 * Test the api_statuses_mediap() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_statuses_mediap('json');
	}
}
