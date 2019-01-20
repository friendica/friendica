<?php

namespace Friendica\Test\API;

class FormatItemsActivitiesTest extends ApiTest
{
	/**
	 * Test the api_format_items_activities() function.
	 * @return void
	 */
	public function testDefault()
	{
		$item = ['uid' => 0, 'uri' => ''];
		$result = api_format_items_activities($item);
		$this->assertArrayHasKey('like', $result);
		$this->assertArrayHasKey('dislike', $result);
		$this->assertArrayHasKey('attendyes', $result);
		$this->assertArrayHasKey('attendno', $result);
		$this->assertArrayHasKey('attendmaybe', $result);
	}

	/**
	 * Test the api_format_items_activities() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$item = ['uid' => 0, 'uri' => ''];
		$result = api_format_items_activities($item, 'xml');
		$this->assertArrayHasKey('friendica:like', $result);
		$this->assertArrayHasKey('friendica:dislike', $result);
		$this->assertArrayHasKey('friendica:attendyes', $result);
		$this->assertArrayHasKey('friendica:attendno', $result);
		$this->assertArrayHasKey('friendica:attendmaybe', $result);
	}
}
