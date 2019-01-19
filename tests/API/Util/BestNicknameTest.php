<?php

namespace Friendica\Test\API;

class BestNicknameTest extends ApiTest
{
	/**
	 * Test the api_best_nickname() function.
	 * @return void
	 */
	public function testDefault()
	{
		$contacts = [];
		$result = api_best_nickname($contacts);
		$this->assertNull($result);
	}

	/**
	 * Test the api_best_nickname() function with contacts.
	 * @return void
	 */
	public function testWithContacts()
	{
		$this->markTestIncomplete();
	}
}
