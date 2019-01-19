<?php

namespace Friendica\Test\API;

class DateTest extends ApiTest
{
	/**
	 * Test the api_date() function.
	 * @return void
	 */
	public function testDefault()
	{
		$this->assertEquals('Wed Oct 10 00:00:00 +0000 1990', api_date('1990-10-10'));
	}
}
