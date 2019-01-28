<?php

namespace Friendica\Test\API\Account;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;

class VerifyCredentialsTest extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use L10nMockTrait;

	/**
	 * Test the api_account_verify_credentials() function.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testApiAccountVerifyCredentials($user, $item)
	{
		$this->mockL10nT();

		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user);

		// Mocking the api_status_show function
		$_REQUEST['skip_status'] = true;

		$this->assertArrayHasKey('user', api_account_verify_credentials('json'));
	}

	/**
	 * Test the api_account_verify_credentials() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiAccountVerifyCredentialsWithoutAuthenticatedUser()
	{
		api_account_verify_credentials('json');
	}
}
