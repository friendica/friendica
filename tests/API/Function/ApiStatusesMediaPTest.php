<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;
use Friendica\Test\Util\Mocks\PhotoMockTrait;

class ApiStatusesMediaPTest extends ApiTest
{
	use ApiUserDatasetTrait;
	use L10nMockTrait;
	use PhotoMockTrait;

	/**
	 * Test the api_statuses_mediap() function.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testDefault($data)
	{
		$this->mockL10nT();

		$this->mockApiUser($data['uid']);
		$this->mockApiGetUser($data);

		$this->mockEscape($data['nick'], 1);
		$stmt = "SELECT `user`.*, `contact`.`id` FROM `user`
				INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
				WHERE `user`.`nickname` = '%s' AND `user`.`blocked` = 0
				AND `contact`.`self` = 1 LIMIT 1";
		$this->mockDBAQ($stmt, [$data], $data['nick'], 1);

		$this->mockConfigGet('system', 'maximagesize', false, 1);
		$this->mockConfigGet('system', 'png_quality', false, 1);
		$this->mockConfigGet('system', 'max_image_length', false, 1);

		$this->mockPhotoNewResource(123, 1);
		$this->mockPhotoStore(true, 1);

		$this->app->argc = 2;

		$_FILES = [
			'media' => [
				'id' => 666,
				'size' => 666,
				'width' => 666,
				'height' => 666,
				'tmp_name' => $this->getTempImage(),
				'name' => 'spacer.png',
				'type' => 'image/png'
			]
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
