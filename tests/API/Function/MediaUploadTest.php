<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;
use Friendica\Test\Util\Mocks\PhotoMockTrait;

class MediaUploadTest extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use L10nMockTrait;
	use PhotoMockTrait;

	/**
	 * Test the api_media_upload() function.
	 * @dataProvider dataApiUserMediaFull
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiMediaUpload($user, $media)
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
	 * @dataProvider dataApiUserMediaFull
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testWithMedia($user, $media)
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
	 * @dataProvider dataApiUserMediaFull
	 * @return void
	 */
	public function testWithValidMedia($user, $media)
	{
		$resId = 123;

		$this->mockL10nT();
		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, 2);
		$this->mockEscape($user['nick'], 1);
		$this->mockDBAQ('SELECT `user`.*, `contact`.`id` FROM `user`
				INNER JOIN `contact` on `user`.`uid` = `contact`.`uid`
				WHERE `user`.`nickname` = \'%s\' AND `user`.`blocked` = 0
				AND `contact`.`self` = 1 LIMIT 1', [$user], $user['nick'], 1);

		$this->mockConfigGet('system', 'maximagesize', false, 1);
		$this->mockConfigGet('system', 'png_quality', false, 1);
		$this->mockConfigGet('system', 'max_image_length', false, 1);

		$this->mockPhotoNewResource($resId, 1);
		$this->mockPhotoStore(true, 1);

		$this->mockDBAQ('SELECT `id`, `datasize`, `width`, `height`, `type` FROM `photo`
			WHERE `resource-id` = \'%s\'
			ORDER BY `width` DESC LIMIT 1', [$media], $resId, 1);

		$_FILES = [
			'media' => $media,
		];
		$this->app->argc = 2;

		$result = api_media_upload();
		$this->assertEquals('image/png', $result['media']['image']['image_type']);
		$this->assertEquals($media['width'], $result['media']['image']['w']);
		$this->assertEquals($media['height'], $result['media']['image']['h']);
	}
}
