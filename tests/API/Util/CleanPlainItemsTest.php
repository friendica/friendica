<?php

namespace Friendica\Test\API;

class CleanPlainItemsTest extends ApiTest
{
	/**
	 * Test the api_clean_plain_items() function.
	 * @return void
	 */
	public function testDefault()
	{
		$_REQUEST['include_entities'] = 'true';
		$result = api_clean_plain_items('some_text [url="some_url"]some_text[/url]');
		$this->assertEquals('some_text [url="some_url"]"some_url"[/url]', $result);
	}
}
