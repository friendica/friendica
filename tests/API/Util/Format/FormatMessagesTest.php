<?php

namespace Friendica\Test\API;

class FormatMessagesTest extends ApiTest
{
	/**
	 * Test the api_format_messages() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_format_messages(
			['id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'screen_name' => 'sender_name']
		);
		$this->assertEquals('item_title'."\n".'item_body', $result['text']);
		$this->assertEquals(1, $result['id']);
		$this->assertEquals(2, $result['recipient_id']);
		$this->assertEquals(3, $result['sender_id']);
		$this->assertEquals('recipient_name', $result['recipient_screen_name']);
		$this->assertEquals('sender_name', $result['sender_screen_name']);
	}

	/**
	 * Test the api_format_messages() function with HTML.
	 * @return void
	 */
	public function testWithHtmlText()
	{
		$_GET['getText'] = 'html';
		$result = api_format_messages(
			['id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'screen_name' => 'sender_name']
		);
		$this->assertEquals('item_title', $result['title']);
		$this->assertEquals('<strong>item_body</strong>', $result['text']);
	}

	/**
	 * Test the api_format_messages() function with plain text.
	 * @return void
	 */
	public function testWithPlainText()
	{
		$_GET['getText'] = 'plain';
		$result = api_format_messages(
			['id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'screen_name' => 'sender_name']
		);
		$this->assertEquals('item_title', $result['title']);
		$this->assertEquals('item_body', $result['text']);
	}

	/**
	 * Test the api_format_messages() function with the getUserObjects GET parameter set to false.
	 * @return void
	 */
	public function testWithoutUserObjects()
	{
		$_GET['getUserObjects'] = 'false';
		$result = api_format_messages(
			['id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'screen_name' => 'sender_name']
		);
		$this->assertTrue(!isset($result['sender']));
		$this->assertTrue(!isset($result['recipient']));
	}
}
