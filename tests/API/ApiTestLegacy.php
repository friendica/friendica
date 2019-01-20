<?php
/**
 * ApiTest class.
 */

namespace Friendica\Test\API;

/**
 * Tests for the API functions.
 *
 * Functions that use header() need to be tested in a separate process.
 * @see https://phpunit.de/manual/5.7/en/appendixes.annotations.html#appendixes.annotations.runTestsInSeparateProcesses
 */
class ApiTestLegacy extends ApiTest
{
	/**
	 * Test the api_date() function.
	 * @return void
	 */
	public function testApiDate()
	{
		$this->assertEquals('Wed Oct 10 00:00:00 +0000 1990', api_date('1990-10-10'));
	}

	/**
	 * Test the api_share_as_retweet() function.
	 * @return void
	 */
	public function testApiShareAsRetweet()
	{
		$item = ['body' => '', 'author-id' => 1, 'owner-id' => 1];
		$result = api_share_as_retweet($item);
		$this->assertFalse($result);
	}

	/**
	 * Test the api_share_as_retweet() function with a valid item.
	 * @return void
	 */
	public function testApiShareAsRetweetWithValidItem()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_get_nick() function.
	 * @return void
	 */
	public function testApiGetNick()
	{
		$result = api_get_nick($this->otherUser['nurl']);
		$this->assertEquals('othercontact', $result);
	}

	/**
	 * Test the api_get_nick() function with a wrong URL.
	 * @return void
	 */
	public function testApiGetNickWithWrongUrl()
	{
		$result = api_get_nick('wrong_url');
		$this->assertFalse($result);
	}

	/**
	 * Test the api_in_reply_to() function.
	 * @return void
	 */
	public function testApiInReplyTo()
	{
		$result = api_in_reply_to(['id' => 0, 'parent' => 0, 'uri' => '', 'thr-parent' => '']);
		$this->assertArrayHasKey('status_id', $result);
		$this->assertArrayHasKey('user_id', $result);
		$this->assertArrayHasKey('status_id_str', $result);
		$this->assertArrayHasKey('user_id_str', $result);
		$this->assertArrayHasKey('screen_name', $result);
	}

	/**
	 * Test the api_in_reply_to() function with a valid item.
	 * @return void
	 */
	public function testApiInReplyToWithValidItem()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_clean_plain_items() function.
	 * @return void
	 */
	public function testApiCleanPlainItems()
	{
		$_REQUEST['include_entities'] = 'true';
		$result = api_clean_plain_items('some_text [url="some_url"]some_text[/url]');
		$this->assertEquals('some_text [url="some_url"]"some_url"[/url]', $result);
	}

	/**
	 * Test the api_clean_attachments() function.
	 * @return void
	 */
	public function testApiCleanAttachments()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_best_nickname() function.
	 * @return void
	 */
	public function testApiBestNickname()
	{
		$contacts = [];
		$result = api_best_nickname($contacts);
		$this->assertNull($result);
	}

	/**
	 * Test the api_best_nickname() function with contacts.
	 * @return void
	 */
	public function testApiBestNicknameWithContacts()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_group_show() function.
	 * @return void
	 */
	public function testApiFriendicaGroupShow()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_group_delete() function.
	 * @return void
	 */
	public function testApiFriendicaGroupDelete()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_lists_destroy() function.
	 * @return void
	 */
	public function testApiListsDestroy()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the group_create() function.
	 * @return void
	 */
	public function testGroupCreate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_group_create() function.
	 * @return void
	 */
	public function testApiFriendicaGroupCreate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_lists_create() function.
	 * @return void
	 */
	public function testApiListsCreate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_group_update() function.
	 * @return void
	 */
	public function testApiFriendicaGroupUpdate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_lists_update() function.
	 * @return void
	 */
	public function testApiListsUpdate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_activity() function.
	 * @return void
	 */
	public function testApiFriendicaActivity()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_notification() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFriendicaNotification()
	{
		api_friendica_notification('json');
	}

	/**
	 * Test the api_friendica_notification() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFriendicaNotificationWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_friendica_notification('json');
	}

	/**
	 * Test the api_friendica_notification() function with an argument count.
	 * @return void
	 */
	public function testApiFriendicaNotificationWithArgumentCount()
	{
		$this->app->argv = ['api', 'friendica', 'notification'];
		$this->app->argc = count($this->app->argv);
		$result = api_friendica_notification('json');
		$this->assertEquals(['note' => false], $result);
	}

	/**
	 * Test the api_friendica_notification() function with an XML result.
	 * @return void
	 */
	public function testApiFriendicaNotificationWithXmlResult()
	{
		$this->app->argv = ['api', 'friendica', 'notification'];
		$this->app->argc = count($this->app->argv);
		$result = api_friendica_notification('xml');
		$this->assertXml($result, 'notes');
	}

	/**
	 * Test the api_friendica_notification_seen() function.
	 * @return void
	 */
	public function testApiFriendicaNotificationSeen()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_direct_messages_setseen() function.
	 * @return void
	 */
	public function testApiFriendicaDirectMessagesSetseen()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_direct_messages_search() function.
	 * @return void
	 */
	public function testApiFriendicaDirectMessagesSearch()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_profile_show() function.
	 * @return void
	 */
	public function testApiFriendicaProfileShow()
	{
		$result = api_friendica_profile_show('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		$this->assertEquals($this->selfUser['id'], $result['$result']['friendica_owner']['cid']);
		$this->assertEquals('DFRN', $result['$result']['friendica_owner']['location']);
		$this->assertEquals($this->selfUser['name'], $result['$result']['friendica_owner']['name']);
		$this->assertEquals($this->selfUser['nick'], $result['$result']['friendica_owner']['screen_name']);
		$this->assertEquals('dfrn', $result['$result']['friendica_owner']['network']);
		$this->assertTrue($result['$result']['friendica_owner']['verified']);
		$this->assertFalse($result['$result']['multi_profiles']);
	}

	/**
	 * Test the api_friendica_profile_show() function with a profile ID.
	 * @return void
	 */
	public function testApiFriendicaProfileShowWithProfileId()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}

	/**
	 * Test the api_friendica_profile_show() function with a wrong profile ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFriendicaProfileShowWithWrongProfileId()
	{
		$_REQUEST['profile_id'] = 666;
		api_friendica_profile_show('json');
	}

	/**
	 * Test the api_friendica_profile_show() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFriendicaProfileShowWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_friendica_profile_show('json');
	}

	/**
	 * Test the api_saved_searches_list() function.
	 * @return void
	 */
	public function testApiSavedSearchesList()
	{
		$result = api_saved_searches_list('json');
		$this->assertEquals(1, $result['terms'][0]['id']);
		$this->assertEquals(1, $result['terms'][0]['id_str']);
		$this->assertEquals('Saved search', $result['terms'][0]['name']);
		$this->assertEquals('Saved search', $result['terms'][0]['query']);
	}
}
