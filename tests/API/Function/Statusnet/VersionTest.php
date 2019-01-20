<?php

namespace Friendica\Test\API\Statusnet;

use Friendica\Test\API\ApiTest;

class VersionTest extends ApiTest
{
	/**
	 * Test the api_statusnet_version() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_statusnet_version('json');
		$this->assertEquals('0.9.7', $result['version']);
	}
}
