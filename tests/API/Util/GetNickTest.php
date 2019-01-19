<?php

namespace Friendica\Test\API;

class GetNickTest extends ApiTest
{
	/**
	 * Test the api_get_nick() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_get_nick($this->otherUser['nurl']);
		$this->assertEquals('othercontact', $result);
	}

	/**
	 * Test the api_get_nick() function with a wrong URL.
	 * @return void
	 */
	public function testWithWrongUrl()
	{
		$result = api_get_nick('wrong_url');
		$this->assertFalse($result);
	}
}
