<?php

namespace Friendica\Test\API;

class InReplyToTest extends ApiTest
{
	/**
	 * Test the api_in_reply_to() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_in_reply_to(['id' => 0, 'parent' => 0, 'uri' => '', 'thr-parent' => '']);
		$this->assertArrayHasKey('status_id', $result);
		$this->assertArrayHasKey('user_id', $result);
		$this->assertArrayHasKey('status_id_str', $result);
		$this->assertArrayHasKey('user_id_str', $result);
		$this->assertArrayHasKey('screen_name', $result);
	}

	/**
	 * Test the api_in_reply_to() function with a valid item.
	 * @return void
	 */
	public function testWithValidItem()
	{
		$this->markTestIncomplete();
	}
}
