<?php

namespace Friendica\Test\API\Account;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\WorkerMockTrait;

/**
 * Tests for api_account_update_profile()
 * @see api_account_update_profile()
 */
class UpdateProfileTest extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use WorkerMockTrait;

	/**
	 * Test the api_account_update_profile() function.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testDefault($user, $item)
	{
		$new_name = 'new_name';
		$new_desc = 'new_description';

		$this->mockApiUser($user['uid']);
		$this->mockApiGetUser($user, null, true, 2);

		// Mocking the NewName Update
		$this->mockUpdate('profile', ['name' => $new_name], ['uid' => $user['uid']], [], true, 1);
		$this->mockUpdate('user', ['username' => $new_name], ['uid' => $user['uid']], [], true, 1);
		$this->mockUpdate('contact', ['name' => $new_name], ['uid' => $user['uid'], 'self' => 1], [], true, 1);
		$this->mockUpdate('contact', ['name' => $new_name], ['id' => $user['id']], [], true, 1);

		// Mocking the NewDescription Update
		$this->mockUpdate('profile', ['about' => $new_desc], ['uid' => $user['uid']], [], true, 1);
		$this->mockUpdate('contact', ['about' => $new_desc], ['uid' => $user['uid'], 'self' => 1], [], true, 1);
		$this->mockUpdate('contact', ['about' => $new_desc], ['id' => $user['id']], [], true, 1);

		// Mocking the ProfileUpdate Worker
		$this->mockWorkerAdd(PRIORITY_LOW, 'ProfileUpdate', $user['uid'], true, 1);
		$this->mockConfigGet('system', 'directory', false, 1);

		// Mocking the api_status_show function
		$_REQUEST['skip_status'] = true;

		$_POST['name'] = $new_name;
		$_POST['description'] = $new_desc;

		$result = api_account_update_profile('json');
		$this->assertUser($result['user'], $user, false);
	}
}
