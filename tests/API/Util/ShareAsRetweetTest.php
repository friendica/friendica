<?php

namespace Friendica\Test\API;

class ShareAsRetweetTest extends ApiTest
{
	/**
	 * Test the api_share_as_retweet() function.
	 * @return void
	 */
	public function testDefault()
	{
		$item = ['body' => '', 'author-id' => 1, 'owner-id' => 1];
		$result = api_share_as_retweet($item);
		$this->assertFalse($result);
	}

	/**
	 * Test the api_share_as_retweet() function with a valid item.
	 * @return void
	 */
	public function testWithValidItem()
	{
		$this->markTestIncomplete();
	}
}
