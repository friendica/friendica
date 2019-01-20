<?php

namespace Friendica\Test\API;

class StatusesFTest extends ApiTest
{
	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testWithFriends()
	{
		$_GET['page'] = -1;
		$result = api_statuses_f('friends');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testWithFollowers()
	{
		$result = api_statuses_f('followers');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testWithBlocks()
	{
		$result = api_statuses_f('blocks');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testWithIncoming()
	{
		$result = api_statuses_f('incoming');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_statuses_f('friends'));
	}
}
