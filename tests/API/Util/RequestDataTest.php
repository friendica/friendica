<?php

namespace Friendica\Test\API;

class RequestDataTest extends ApiTest
{
	/**
	 * Test the requestdata() function.
	 * @return void
	 */
	public function testDefault()
	{
		$this->assertNull(requestdata('variable_name'));
	}

	/**
	 * Test the requestdata() function with a POST parameter.
	 * @return void
	 */
	public function testWithPost()
	{
		$_POST['variable_name'] = 'variable_value';
		$this->assertEquals('variable_value', requestdata('variable_name'));
	}

	/**
	 * Test the requestdata() function with a GET parameter.
	 * @return void
	 */
	public function testWithGet()
	{
		$_GET['variable_name'] = 'variable_value';
		$this->assertEquals('variable_value', requestdata('variable_name'));
	}

}
