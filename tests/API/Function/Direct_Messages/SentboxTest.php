<?php

namespace Friendica\Test\API\Direct_Messages;

use Friendica\Test\API\ApiTest;

class SentboxTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_sentbox() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_direct_messages_sentbox('json');
		$this->assertArrayHasKey('direct_message', $result);
	}
}
