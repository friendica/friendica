<?php

namespace Friendica\Test\API;

class ContactLinkToArrayTest extends ApiTest
{
	/**
	 * Test the api_contactlink_to_array() function.
	 * @return void
	 */
	public function testDefault()
	{
		$this->assertEquals(
			[
				'name' => 'text',
				'url' => '',
			],
			api_contactlink_to_array('text')
		);
	}

	/**
	 * Test the api_contactlink_to_array() function with an URL.
	 * @return void
	 */
	public function testWithUrl()
	{
		$this->assertEquals(
			[
				'name' => ['link_text'],
				'url' => ['url'],
			],
			api_contactlink_to_array('text <a href="url">link_text</a>')
		);
	}
}
