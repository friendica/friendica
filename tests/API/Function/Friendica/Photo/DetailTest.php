<?php

namespace Friendica\Test\API\Friendica\Photo;

use Friendica\Test\API\ApiTest;

class DetailTest extends ApiTest
{
	/**
	 * Test the api_fr_photo_detail() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function with a photo ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\NotFoundException
	 */
	public function testWithPhotoId()
	{
		$_REQUEST['photo_id'] = 1;
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function with a correct photo ID.
	 * @return void
	 */
	public function testWithCorrectPhotoId()
	{
		$this->markTestIncomplete('We need to create a dataset for this.');
	}
}
