<?php

namespace Friendica\Test\API\Direct_Messages;

use Friendica\Test\API\ApiTest;

class AllTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_all() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_direct_messages_all('json');
		$this->assertArrayHasKey('direct_message', $result);
	}
}
