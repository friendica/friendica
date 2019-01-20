<?php

namespace Friendica\Test\API\Friendica\Photo;

use Friendica\Test\API\ApiTest;

class DeleteTest extends ApiTest
{
	/**
	 * Test the api_fr_photo_delete() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_fr_photo_delete('json');
	}

	/**
	 * Test the api_fr_photo_delete() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photo_delete('json');
	}

	/**
	 * Test the api_fr_photo_delete() function with a photo ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithPhotoId()
	{
		$_REQUEST['photo_id'] = 1;
		api_fr_photo_delete('json');
	}

	/**
	 * Test the api_fr_photo_delete() function with a correct photo ID.
	 * @return void
	 */
	public function testWithCorrectPhotoId()
	{
		$this->markTestIncomplete('We need to create a dataset for this.');
	}
}
