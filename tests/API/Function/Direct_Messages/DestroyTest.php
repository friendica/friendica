<?php

namespace Friendica\Test\API\Direct_Messages;

use Friendica\Test\API\ApiTest;

class DestroyTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_destroy() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() function with the friendica_verbose GET param.
	 * @return void
	 */
	public function testWithVerbose()
	{
		$_GET['friendica_verbose'] = 'true';
		$result = api_direct_messages_destroy('json');
		$this->assertEquals(
			[
				'$result' => [
					'result' => 'error',
					'message' => 'message id or parenturi not specified'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_destroy() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithId()
	{
		$_REQUEST['id'] = 1;
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() with a non-zero ID and the friendica_verbose GET param.
	 * @return void
	 */
	public function testWithIdAndVerbose()
	{
		$_REQUEST['id'] = 1;
		$_REQUEST['friendica_parenturi'] = 'parent_uri';
		$_GET['friendica_verbose'] = 'true';
		$result = api_direct_messages_destroy('json');
		$this->assertEquals(
			[
				'$result' => [
					'result' => 'error',
					'message' => 'message id not in database'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 * @return void
	 */
	public function testWithCorrectId()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}
}
