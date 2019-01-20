<?php

namespace Friendica\Test\API;

use Friendica\Model\Item;
use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\BBCodeMockTrait;
use Friendica\Test\Util\Mocks\ItemMockTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;

class ApiAccountVerifyCredentialsTest extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use BBCodeMockTrait;
	use ItemMockTrait;
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

		$this->mockItemConstants();
		$this->mockItemSelectFirst(Item::ITEM_FIELDLIST, [], [], $item, 1);
		$this->mockIsResult($item, true, 1);

		$this->mockCleanPictureLinks($item['body'], $item['body'], 1);
		$this->mockGetAttachmentData($item['body'], [], 2);
		$this->mockConvert("", "", false, false, false, 3);

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
