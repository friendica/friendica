<?php

namespace Friendica\Test\API\Direct_Messages;

use Friendica\Test\API\ApiTest;

class BoxTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testWithSentbox()
	{
		$_REQUEST['page'] = -1;
		$_REQUEST['max_id'] = 10;
		$result = api_direct_messages_box('json', 'sentbox', 'false');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testWithConversation()
	{
		$result = api_direct_messages_box('json', 'conversation', 'false');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testWithAll()
	{
		$result = api_direct_messages_box('json', 'all', 'false');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testWithInbox()
	{
		$result = api_direct_messages_box('json', 'inbox', 'false');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testWithVerbose()
	{
		$result = api_direct_messages_box('json', 'sentbox', 'true');
		$this->assertEquals(
			[
				'$result' => [
					'result' => 'error',
					'message' => 'no mails available'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_box() function with a RSS result.
	 * @return void
	 */
	public function testWithRss()
	{
		$result = api_direct_messages_box('rss', 'sentbox', 'false');
		$this->assertXml($result, 'direct-messages');
	}

	/**
	 * Test the api_direct_messages_box() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_direct_messages_box('json', 'sentbox', 'false');
	}
}
