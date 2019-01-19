<?php

namespace Friendica\Test\Api;

use Friendica\Test\ApiTest;

class ApiCheckMethodTest extends ApiTest
{
	/**
	 * Test the api_check_method() function.
	 * @return void
	 */
	public function testDefault()
	{
		$this->assertFalse(api_check_method('method'));
	}

	/**
	 * Test the api_check_method() function with a correct method.
	 * @return void
	 */
	public function testWithCorrectMethod()
	{
		$_SERVER['REQUEST_METHOD'] = 'method';
		$this->assertTrue(api_check_method('method'));
	}

	/**
	 * Test the api_check_method() function with a wildcard.
	 * @return void
	 */
	public function testWithWildcard()
	{
		$this->assertTrue(api_check_method('*'));
	}
}
