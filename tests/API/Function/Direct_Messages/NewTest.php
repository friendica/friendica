<?php

namespace Friendica\Test\API\Direct_Messages;

use Friendica\Test\API\ApiTest;

class NewTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_new() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_direct_messages_new('json');
		$this->assertNull($result);
	}

	/**
	 * Test the api_direct_messages_new() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_direct_messages_new('json');
	}

	/**
	 * Test the api_direct_messages_new() function with an user ID.
	 * @return void
	 */
	public function testWithUserId()
	{
		$_POST['text'] = 'message_text';
		$_POST['user_id'] = $this->otherUser['id'];
		$result = api_direct_messages_new('json');
		$this->assertEquals(['direct_message' => ['error' => -1]], $result);
	}

	/**
	 * Test the api_direct_messages_new() function with a screen name.
	 * @return void
	 */
	public function testWithScreenName()
	{
		$_POST['text'] = 'message_text';
		$_POST['screen_name'] = $this->friendUser['nick'];
		$result = api_direct_messages_new('json');
		$this->assertEquals(1, $result['direct_message']['id']);
		$this->assertContains('message_text', $result['direct_message']['text']);
		$this->assertEquals('selfcontact', $result['direct_message']['sender_screen_name']);
		$this->assertEquals(1, $result['direct_message']['friendica_seen']);
	}

	/**
	 * Test the api_direct_messages_new() function with a title.
	 * @return void
	 */
	public function testWithTitle()
	{
		$_POST['text'] = 'message_text';
		$_POST['screen_name'] = $this->friendUser['nick'];
		$_REQUEST['title'] = 'message_title';
		$result = api_direct_messages_new('json');
		$this->assertEquals(1, $result['direct_message']['id']);
		$this->assertContains('message_text', $result['direct_message']['text']);
		$this->assertContains('message_title', $result['direct_message']['text']);
		$this->assertEquals('selfcontact', $result['direct_message']['sender_screen_name']);
		$this->assertEquals(1, $result['direct_message']['friendica_seen']);
	}

	/**
	 * Test the api_direct_messages_new() function with an RSS result.
	 * @return void
	 */
	public function testWithRss()
	{
		$_POST['text'] = 'message_text';
		$_POST['screen_name'] = $this->friendUser['nick'];
		$result = api_direct_messages_new('rss');
		$this->assertXml($result, 'direct-messages');
	}
}
