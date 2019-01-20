<?php

namespace Friendica\Test\API;

class RegisterFuncTest extends ApiTest
{
	/**
	 * Test the api_register_func() function.
	 * @return void
	 */
	public function testApiRegisterFunc()
	{
		global $API;
		$this->assertNull(
			api_register_func(
				'api_path',
				function () {
				},
				true,
				'method'
			)
		);
		$this->assertTrue($API['api_path']['auth']);
		$this->assertEquals('method', $API['api_path']['method']);
		$this->assertTrue(is_callable($API['api_path']['func']));
	}
}
