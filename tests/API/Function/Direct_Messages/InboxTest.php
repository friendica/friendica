<?php

namespace Friendica\Test\API\Direct_Messages;

use Friendica\Test\API\ApiTest;

class InboxTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_inbox() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_direct_messages_inbox('json');
		$this->assertArrayHasKey('direct_message', $result);
	}
}
