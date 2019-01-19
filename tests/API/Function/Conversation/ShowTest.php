<?php

namespace Friendica\Test\API\Conversation;

use Friendica\Test\API\ApiTest;

class ShowTest extends ApiTest
{
	/**
	 * Test the api_blocks_list() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_blocks_list('json');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_blocks_list() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_blocks_list('json'));
	}
}
