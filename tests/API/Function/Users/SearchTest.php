<?php

namespace Friendica\Test\API\Statuses;

use Friendica\Test\API\ApiTest;
use Friendica\Test\Util\ApiUserDatasetTrait;
use Friendica\Test\Util\Mocks\L10nMockTrait;

class SearchTest extends ApiTest
{
	use ApiUserDatasetTrait;
	use L10nMockTrait;
	/**
	 * Test the api_users_search() function.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testDefault($user)
	{
		$_GET['q'] = $user['nick'];
		$result = api_users_search('json');
		$this->assertUser($result['users'][0], $user);
	}

	/**
	 * Test the api_users_search() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$_GET['q'] = 'othercontact';
		$result = api_users_search('xml');
		$this->assertXml($result, 'users');
	}

	/**
	 * Test the api_users_search() function without a GET q parameter.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithoutQuery()
	{
		api_users_search('json');
	}
}
