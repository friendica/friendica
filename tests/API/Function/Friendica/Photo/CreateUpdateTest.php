<?php

namespace Friendica\Test\API\Friendica\Photo;

use Friendica\Test\API\ApiTest;

class CreateUpdateTest extends ApiTest
{
	/**
	 * Test the api_fr_photo_create_update() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function with an album name.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithAlbum()
	{
		$_REQUEST['album'] = 'album_name';
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function with the update mode.
	 * @return void
	 */
	public function testWithUpdate()
	{
		$this->markTestIncomplete('We need to create a dataset for this');
	}

	/**
	 * Test the api_fr_photo_create_update() function with an uploaded file.
	 * @return void
	 */
	public function testWithFile()
	{
		$this->markTestIncomplete();
	}
}
