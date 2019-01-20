<?php

namespace Friendica\Test\API\Account;

use Friendica\Test\API\ApiTest;

class RateLimitStatusTest extends ApiTest
{
	/**
	 * Test the api_format_items() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_account_rate_limit_status('json');
		$this->assertEquals(150, $result['hash']['remaining_hits']);
		$this->assertEquals(150, $result['hash']['hourly_limit']);
		$this->assertInternalType('int', $result['hash']['reset_time_in_seconds']);
	}

	/**
	 * Test the api_format_items() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$result = api_account_rate_limit_status('xml');
		$this->assertXml($result, 'hash');
	}
}
