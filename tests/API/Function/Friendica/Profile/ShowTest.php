<?php

namespace Friendica\Test\API\Friendica\Profile;

use Friendica\Test\API\ApiTest;

class ShowTest extends ApiTest
{
	/**
	 * Test the api_friendica_profile_show() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_friendica_profile_show('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		$this->assertEquals($this->selfUser['id'], $result['$result']['friendica_owner']['cid']);
		$this->assertEquals('DFRN', $result['$result']['friendica_owner']['location']);
		$this->assertEquals($this->selfUser['name'], $result['$result']['friendica_owner']['name']);
		$this->assertEquals($this->selfUser['nick'], $result['$result']['friendica_owner']['screen_name']);
		$this->assertEquals('dfrn', $result['$result']['friendica_owner']['network']);
		$this->assertTrue($result['$result']['friendica_owner']['verified']);
		$this->assertFalse($result['$result']['multi_profiles']);
	}

	/**
	 * Test the api_friendica_profile_show() function with a profile ID.
	 * @return void
	 */
	public function testWithProfileId()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}

	/**
	 * Test the api_friendica_profile_show() function with a wrong profile ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithWrongProfileId()
	{
		$_REQUEST['profile_id'] = 666;
		api_friendica_profile_show('json');
	}

	/**
	 * Test the api_friendica_profile_show() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		api_friendica_profile_show('json');
	}
}
