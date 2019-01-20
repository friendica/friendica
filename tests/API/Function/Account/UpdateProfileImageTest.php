<?php

namespace Friendica\Test\API\Account;

use Friendica\Test\API\ApiTest;

class UpdateProfileImageTest extends ApiTest
{
	/**
	 * Test the api_account_update_profile_image() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_account_update_profile_image('json');
	}

	/**
	 * Test the api_account_update_profile_image() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		api_account_update_profile_image('json');
	}

	/**
	 * Test the api_account_update_profile_image() function with an uploaded file.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithUpload()
	{
		$this->markTestIncomplete();
	}
}
