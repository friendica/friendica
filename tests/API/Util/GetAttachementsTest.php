<?php

namespace Friendica\Test\API;

class GetAttachementsTest extends ApiTest
{
	/**
	 * Test the api_get_attachments() function.
	 * @return void
	 */
	public function testDefault()
	{
		$body = 'body';
		$this->assertEmpty(api_get_attachments($body));
	}

	/**
	 * Test the api_get_attachments() function with an img tag.
	 * @return void
	 */
	public function testWithImage()
	{
		$body = '[img]http://via.placeholder.com/1x1.png[/img]';
		$this->assertInternalType('array', api_get_attachments($body));
	}

	/**
	 * Test the api_get_attachments() function with an img tag and an AndStatus user agent.
	 * @return void
	 */
	public function testWithImageAndAndStatus()
	{
		$_SERVER['HTTP_USER_AGENT'] = 'AndStatus';
		$body = '[img]http://via.placeholder.com/1x1.png[/img]';
		$this->assertInternalType('array', api_get_attachments($body));
	}
}
