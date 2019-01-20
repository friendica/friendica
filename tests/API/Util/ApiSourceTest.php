<?php

namespace Friendica\Test\API;

class ApiSourceTest extends ApiTest
{
	public function dataApiSource()
	{
		return [
			'empty' => [
				'input' => null,
				'output' => 'api',
			],
			'twidere' => [
				'input' => 'Twidere',
				'output' => 'Twidere',
			],
			'wrong' => [
				'input' => 'something',
				'output' => 'api',
			],
			'wrong2' => [
				'input' => 'twiddere',
				'output' => 'api',
			]
		];
	}

	/**
	 * Test the api_source() function.
	 * @dataProvider dataApiSource
	 * @return void
	 */
	public function testApiSource($input, $output)
	{
		$_SERVER['HTTP_USER_AGENT'] = $input;

		$this->assertEquals($output, api_source());
	}

	/**
	 * Test the api_source() function with a GET parameter.
	 * @dataProvider dataApiSource
	 * @return void
	 */
	public function testApiSourceWithGet($input, $output)
	{
		$_GET['source'] = $input;

		if (empty($input)) {
			$this->assertEquals($output, api_source());
		} else {
			$this->assertEquals($input, api_source());
		}
	}
}
