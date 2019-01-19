<?php

namespace Friendica\Test\API\Direct_Messages;

use Friendica\Test\API\ApiTest;

class ConversationTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_conversation() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_direct_messages_conversation('json');
		$this->assertArrayHasKey('direct_message', $result);
	}
}
