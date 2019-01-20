<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserItemDatasetTrait;

class ApiMediaUploadTest extends ApiTest
{
	use ApiUserItemDatasetTrait;

	/**
	 * Test the api_media_upload() function.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiMediaUpload($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 1);

		api_media_upload();
	}

	/**
	 * Test the api_media_upload() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		api_media_upload();
	}

	/**
	 * Test the api_media_upload() function with an invalid uploaded media.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testWithMedia($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 1);

		$_FILES = [
			'media' => [
				'id' => 666,
				'tmp_name' => 'tmp_name'
			]
		];
		api_media_upload();
	}

	/**
	 * Test the api_media_upload() function with an valid uploaded media.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testWithValidMedia($user, $item)
	{
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 1);
		$this->mockEscape($user['nick'], 1);
		$this->mockDBAQ('', '', [], 1);

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
		$this->app->argc = 2;

		$result = api_media_upload();
		$this->assertEquals('image/png', $result['media']['image']['image_type']);
		$this->assertEquals(1, $result['media']['image']['w']);
		$this->assertEquals(1, $result['media']['image']['h']);
	}
}
