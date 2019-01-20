<?php

namespace Friendica\Test\API\Friendships;

use Friendica\Test\API\ApiTest;

class IncomingTest extends ApiTest
{
	/**
	 * Test the api_friendships_incoming() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_friendships_incoming('json');
		$this->assertArrayHasKey('id', $result);
	}

	/**
	 * Test the api_friendships_incoming() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_friendships_incoming('json'));
	}
}
