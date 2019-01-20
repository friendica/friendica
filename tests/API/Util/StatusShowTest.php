<?php

namespace Friendica\Test\API;

class StatusShowTest extends ApiTest
{
	/**
	 * Test the api_status_show() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_status_show('json');
		$this->assertStatus($result['status']);
	}

	/**
	 * Test the api_status_show() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$result = api_status_show('xml');
		$this->assertXml($result, 'statuses');
	}

	/**
	 * Test the api_status_show() function with a raw result.
	 * @return void
	 */
	public function testWithRaw()
	{
		$this->assertStatus(api_status_show('raw'));
	}
}
