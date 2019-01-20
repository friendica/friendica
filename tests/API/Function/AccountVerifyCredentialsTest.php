<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;

class AccountVerifyCredentialsTest extends ApiTest
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
		$this->mockApiGetUser($user, 2);
		$this->mockApiStatusShow($item, 1);

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
