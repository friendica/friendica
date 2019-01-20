<?php

namespace Friendica\Test\API\Saved_Searches;

use Friendica\Test\API\ApiTest;

class ListTest extends ApiTest
{
	/**
	 * Test the api_saved_searches_list() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_saved_searches_list('json');
		$this->assertEquals(1, $result['terms'][0]['id']);
		$this->assertEquals(1, $result['terms'][0]['id_str']);
		$this->assertEquals('Saved search', $result['terms'][0]['name']);
		$this->assertEquals('Saved search', $result['terms'][0]['query']);
	}
}
