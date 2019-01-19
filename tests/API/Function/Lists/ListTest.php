<?php

namespace Friendica\Test\API\Lists;

use Friendica\Test\API\ApiTest;

class ListTest extends ApiTest
{
	/**
	 * Test the api_lists_list() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_lists_list('json');
		$this->assertEquals(['lists_list' => []], $result);
	}
}
