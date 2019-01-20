<?php

namespace Friendica\Test\API\Statusnet;

use Friendica\Test\API\ApiTest;

class ConfigTest extends ApiTest
{
	/**
	 * Test the api_statusnet_config() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_statusnet_config('json');
		$this->assertEquals('localhost', $result['config']['site']['server']);
		$this->assertEquals('default', $result['config']['site']['theme']);
		$this->assertEquals(System::baseUrl() . '/images/friendica-64.png', $result['config']['site']['logo']);
		$this->assertTrue($result['config']['site']['fancy']);
		$this->assertEquals('en', $result['config']['site']['language']);
		$this->assertEquals('UTC', $result['config']['site']['timezone']);
		$this->assertEquals(200000, $result['config']['site']['textlimit']);
		$this->assertEquals('false', $result['config']['site']['private']);
		$this->assertEquals('false', $result['config']['site']['ssl']);
		$this->assertEquals(30, $result['config']['site']['shorturllength']);
	}
}
