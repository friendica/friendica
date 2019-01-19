<?php

namespace Friendica\Test\API\Help;

use Friendica\Test\API\ApiTest;

class HelpTest extends ApiTest
{
	/**
	 * Test the api_help_test() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_help_test('json');
		$this->assertEquals(['ok' => 'ok'], $result);
	}

	/**
	 * Test the api_help_test() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$result = api_help_test('xml');
		$this->assertXml($result, 'ok');
	}
}
