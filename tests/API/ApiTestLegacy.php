<?php
/**
 * ApiTest class.
 */

namespace Friendica\Test\API;

use Friendica\Core\Protocol;
use Friendica\Core\System;

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
	 * Test the api_format_items_embeded_images() function.
	 * @return void
	 */
	public function testApiFormatItemsEmbededImages()
	{
		$this->assertEquals(
			'text ' . System::baseUrl() . '/display/item_guid',
			api_format_items_embeded_images(['guid' => 'item_guid'], 'text data:image/foo')
		);
	}

	/**
	 * Test the api_format_items_activities() function.
	 * @return void
	 */
	public function testApiFormatItemsActivities()
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
	public function testApiFormatItemsActivitiesWithXml()
	{
		$item = ['uid' => 0, 'uri' => ''];
		$result = api_format_items_activities($item, 'xml');
		$this->assertArrayHasKey('friendica:like', $result);
		$this->assertArrayHasKey('friendica:dislike', $result);
		$this->assertArrayHasKey('friendica:attendyes', $result);
		$this->assertArrayHasKey('friendica:attendno', $result);
		$this->assertArrayHasKey('friendica:attendmaybe', $result);
	}

	/**
	 * Test the api_format_items_profiles() function.
	 * @return void
	 */
	public function testApiFormatItemsProfiles()
	{
		$profile_row = [
			'id' => 'profile_id',
			'profile-name' => 'profile_name',
			'is-default' => true,
			'hide-friends' => true,
			'photo' => 'profile_photo',
			'thumb' => 'profile_thumb',
			'publish' => true,
			'net-publish' => true,
			'pdesc' => 'description',
			'dob' => 'date_of_birth',
			'address' => 'address',
			'locality' => 'city',
			'region' => 'region',
			'postal-code' => 'postal_code',
			'country-name' => 'country',
			'hometown' => 'hometown',
			'gender' => 'gender',
			'marital' => 'marital',
			'with' => 'marital_with',
			'howlong' => 'marital_since',
			'sexual' => 'sexual',
			'politic' => 'politic',
			'religion' => 'religion',
			'pub_keywords' => 'public_keywords',
			'prv_keywords' => 'private_keywords',

			'likes' => 'likes',
			'dislikes' => 'dislikes',
			'about' => 'about',
			'music' => 'music',
			'book' => 'book',
			'tv' => 'tv',
			'film' => 'film',
			'interest' => 'interest',
			'romance' => 'romance',
			'work' => 'work',
			'education' => 'education',
			'contact' => 'social_networks',
			'homepage' => 'homepage'
		];
		$result = api_format_items_profiles($profile_row);
		$this->assertEquals(
			[
				'profile_id' => 'profile_id',
				'profile_name' => 'profile_name',
				'is_default' => true,
				'hide_friends' => true,
				'profile_photo' => 'profile_photo',
				'profile_thumb' => 'profile_thumb',
				'publish' => true,
				'net_publish' => true,
				'description' => 'description',
				'date_of_birth' => 'date_of_birth',
				'address' => 'address',
				'city' => 'city',
				'region' => 'region',
				'postal_code' => 'postal_code',
				'country' => 'country',
				'hometown' => 'hometown',
				'gender' => 'gender',
				'marital' => 'marital',
				'marital_with' => 'marital_with',
				'marital_since' => 'marital_since',
				'sexual' => 'sexual',
				'politic' => 'politic',
				'religion' => 'religion',
				'public_keywords' => 'public_keywords',
				'private_keywords' => 'private_keywords',

				'likes' => 'likes',
				'dislikes' => 'dislikes',
				'about' => 'about',
				'music' => 'music',
				'book' => 'book',
				'tv' => 'tv',
				'film' => 'film',
				'interest' => 'interest',
				'romance' => 'romance',
				'work' => 'work',
				'education' => 'education',
				'social_networks' => 'social_networks',
				'homepage' => 'homepage',
				'users' => null
			],
			$result
		);
	}

	/**
	 * Test the api_format_items() function.
	 * @return void
	 */
	public function testApiFormatItems()
	{
		$items = [
			[
				'item_network' => 'item_network',
				'source' => 'web',
				'coord' => '5 7',
				'body' => '',
				'verb' => '',
				'author-id' => 43,
				'author-network' => Protocol::DFRN,
				'author-link' => 'http://localhost/profile/othercontact',
				'plink' => '',
			]
		];
		$result = api_format_items($items, ['id' => 0], true);
		foreach ($result as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_format_items() function with an XML result.
	 * @return void
	 */
	public function testApiFormatItemsWithXml()
	{
		$items = [
			[
				'coord' => '5 7',
				'body' => '',
				'verb' => '',
				'author-id' => 43,
				'author-network' => Protocol::DFRN,
				'author-link' => 'http://localhost/profile/othercontact',
				'plink' => '',
			]
		];
		$result = api_format_items($items, ['id' => 0], true, 'xml');
		foreach ($result as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_format_items() function.
	 * @return void
	 */
	public function testApiAccountRateLimitStatus()
	{
		$result = api_account_rate_limit_status('json');
		$this->assertEquals(150, $result['hash']['remaining_hits']);
		$this->assertEquals(150, $result['hash']['hourly_limit']);
		$this->assertInternalType('int', $result['hash']['reset_time_in_seconds']);
	}

	/**
	 * Test the api_format_items() function with an XML result.
	 * @return void
	 */
	public function testApiAccountRateLimitStatusWithXml()
	{
		$result = api_account_rate_limit_status('xml');
		$this->assertXml($result, 'hash');
	}

	/**
	 * Test the api_help_test() function.
	 * @return void
	 */
	public function testApiHelpTest()
	{
		$result = api_help_test('json');
		$this->assertEquals(['ok' => 'ok'], $result);
	}

	/**
	 * Test the api_help_test() function with an XML result.
	 * @return void
	 */
	public function testApiHelpTestWithXml()
	{
		$result = api_help_test('xml');
		$this->assertXml($result, 'ok');
	}

	/**
	 * Test the api_lists_list() function.
	 * @return void
	 */
	public function testApiListsList()
	{
		$result = api_lists_list('json');
		$this->assertEquals(['lists_list' => []], $result);
	}

	/**
	 * Test the api_lists_ownerships() function.
	 * @return void
	 */
	public function testApiListsOwnerships()
	{
		$result = api_lists_ownerships('json');
		foreach ($result['lists']['lists'] as $list) {
			$this->assertList($list);
		}
	}

	/**
	 * Test the api_lists_ownerships() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiListsOwnershipsWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_lists_ownerships('json');
	}

	/**
	 * Test the api_lists_statuses() function.
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 * @return void
	 */
	public function testApiListsStatuses()
	{
		api_lists_statuses('json');
	}

	/**
	 * Test the api_lists_statuses() function with a list ID.
	 * @return void
	 */
	public function testApiListsStatusesWithListId()
	{
		$_REQUEST['list_id'] = 1;
		$_REQUEST['page'] = -1;
		$_REQUEST['max_id'] = 10;
		$result = api_lists_statuses('json');
		foreach ($result['status'] as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_lists_statuses() function with a list ID and a RSS result.
	 * @return void
	 */
	public function testApiListsStatusesWithListIdAndRss()
	{
		$_REQUEST['list_id'] = 1;
		$result = api_lists_statuses('rss');
		$this->assertXml($result, 'statuses');
	}

	/**
	 * Test the api_lists_statuses() function with an unallowed user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiListsStatusesWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_lists_statuses('json');
	}

	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testApiStatusesFWithFriends()
	{
		$_GET['page'] = -1;
		$result = api_statuses_f('friends');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testApiStatusesFWithFollowers()
	{
		$result = api_statuses_f('followers');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testApiStatusesFWithBlocks()
	{
		$result = api_statuses_f('blocks');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function.
	 * @return void
	 */
	public function testApiStatusesFWithIncoming()
	{
		$result = api_statuses_f('incoming');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testApiStatusesFWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_statuses_f('friends'));
	}

	/**
	 * Test the api_statuses_friends() function.
	 * @return void
	 */
	public function testApiStatusesFriends()
	{
		$result = api_statuses_friends('json');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_friends() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testApiStatusesFriendsWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_statuses_friends('json'));
	}

	/**
	 * Test the api_statuses_followers() function.
	 * @return void
	 */
	public function testApiStatusesFollowers()
	{
		$result = api_statuses_followers('json');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_followers() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testApiStatusesFollowersWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_statuses_followers('json'));
	}

	/**
	 * Test the api_blocks_list() function.
	 * @return void
	 */
	public function testApiBlocksList()
	{
		$result = api_blocks_list('json');
		$this->assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_blocks_list() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testApiBlocksListWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_blocks_list('json'));
	}

	/**
	 * Test the api_friendships_incoming() function.
	 * @return void
	 */
	public function testApiFriendshipsIncoming()
	{
		$result = api_friendships_incoming('json');
		$this->assertArrayHasKey('id', $result);
	}

	/**
	 * Test the api_friendships_incoming() function an undefined cursor GET variable.
	 * @return void
	 */
	public function testApiFriendshipsIncomingWithUndefinedCursor()
	{
		$_GET['cursor'] = 'undefined';
		$this->assertFalse(api_friendships_incoming('json'));
	}

	/**
	 * Test the api_statusnet_config() function.
	 * @return void
	 */
	public function testApiStatusnetConfig()
	{
		$result = api_statusnet_config('json');
		$this->assertEquals('localhost', $result['config']['site']['server']);
		$this->assertEquals('default', $result['config']['site']['theme']);
		$this->assertEquals(System::baseUrl() . '/images/friendica-64.png', $result['config']['site']['logo']);
		$this->assertTrue($result['config']['site']['fancy']);
		$this->assertEquals('en', $result['config']['site']['language']);
		$this->assertEquals('UTC', $result['config']['site']['timezone']);
		$this->assertEquals(200000, $result['config']['site']['textlimit']);
		$this->assertEquals('false', $result['config']['site']['private']);
		$this->assertEquals('false', $result['config']['site']['ssl']);
		$this->assertEquals(30, $result['config']['site']['shorturllength']);
	}

	/**
	 * Test the api_statusnet_version() function.
	 * @return void
	 */
	public function testApiStatusnetVersion()
	{
		$result = api_statusnet_version('json');
		$this->assertEquals('0.9.7', $result['version']);
	}

	/**
	 * Test the api_ff_ids() function.
	 * @return void
	 */
	public function testApiFfIds()
	{
		$result = api_ff_ids('json');
		$this->assertNull($result);
	}

	/**
	 * Test the api_ff_ids() function with a result.
	 * @return void
	 */
	public function testApiFfIdsWithResult()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_ff_ids() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFfIdsWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_ff_ids('json');
	}

	/**
	 * Test the api_friends_ids() function.
	 * @return void
	 */
	public function testApiFriendsIds()
	{
		$result = api_friends_ids('json');
		$this->assertNull($result);
	}

	/**
	 * Test the api_followers_ids() function.
	 * @return void
	 */
	public function testApiFollowersIds()
	{
		$result = api_followers_ids('json');
		$this->assertNull($result);
	}

	/**
	 * Test the api_direct_messages_new() function.
	 * @return void
	 */
	public function testApiDirectMessagesNew()
	{
		$result = api_direct_messages_new('json');
		$this->assertNull($result);
	}

	/**
	 * Test the api_direct_messages_new() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiDirectMessagesNewWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_direct_messages_new('json');
	}

	/**
	 * Test the api_direct_messages_new() function with an user ID.
	 * @return void
	 */
	public function testApiDirectMessagesNewWithUserId()
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
	public function testApiDirectMessagesNewWithScreenName()
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
	public function testApiDirectMessagesNewWithTitle()
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
	public function testApiDirectMessagesNewWithRss()
	{
		$_POST['text'] = 'message_text';
		$_POST['screen_name'] = $this->friendUser['nick'];
		$result = api_direct_messages_new('rss');
		$this->assertXml($result, 'direct-messages');
	}

	/**
	 * Test the api_direct_messages_destroy() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiDirectMessagesDestroy()
	{
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() function with the friendica_verbose GET param.
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithVerbose()
	{
		$_GET['friendica_verbose'] = 'true';
		$result = api_direct_messages_destroy('json');
		$this->assertEquals(
			[
				'$result' => [
					'result' => 'error',
					'message' => 'message id or parenturi not specified'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_destroy() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiDirectMessagesDestroyWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiDirectMessagesDestroyWithId()
	{
		$_REQUEST['id'] = 1;
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() with a non-zero ID and the friendica_verbose GET param.
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithIdAndVerbose()
	{
		$_REQUEST['id'] = 1;
		$_REQUEST['friendica_parenturi'] = 'parent_uri';
		$_GET['friendica_verbose'] = 'true';
		$result = api_direct_messages_destroy('json');
		$this->assertEquals(
			[
				'$result' => [
					'result' => 'error',
					'message' => 'message id not in database'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithCorrectId()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithSentbox()
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
	public function testApiDirectMessagesBoxWithConversation()
	{
		$result = api_direct_messages_box('json', 'conversation', 'false');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithAll()
	{
		$result = api_direct_messages_box('json', 'all', 'false');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithInbox()
	{
		$result = api_direct_messages_box('json', 'inbox', 'false');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithVerbose()
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
	public function testApiDirectMessagesBoxWithRss()
	{
		$result = api_direct_messages_box('rss', 'sentbox', 'false');
		$this->assertXml($result, 'direct-messages');
	}

	/**
	 * Test the api_direct_messages_box() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiDirectMessagesBoxWithUnallowedUser()
	{
		$_SESSION['allow_api'] = false;
		$_GET['screen_name'] = $this->selfUser['nick'];
		api_direct_messages_box('json', 'sentbox', 'false');
	}

	/**
	 * Test the api_direct_messages_sentbox() function.
	 * @return void
	 */
	public function testApiDirectMessagesSentbox()
	{
		$result = api_direct_messages_sentbox('json');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_inbox() function.
	 * @return void
	 */
	public function testApiDirectMessagesInbox()
	{
		$result = api_direct_messages_inbox('json');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_all() function.
	 * @return void
	 */
	public function testApiDirectMessagesAll()
	{
		$result = api_direct_messages_all('json');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_conversation() function.
	 * @return void
	 */
	public function testApiDirectMessagesConversation()
	{
		$result = api_direct_messages_conversation('json');
		$this->assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_oauth_request_token() function.
	 * @return void
	 */
	public function testApiOauthRequestToken()
	{
		$this->markTestIncomplete('killme() kills phpunit as well');
	}

	/**
	 * Test the api_oauth_access_token() function.
	 * @return void
	 */
	public function testApiOauthAccessToken()
	{
		$this->markTestIncomplete('killme() kills phpunit as well');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoalbumDelete()
	{
		api_fr_photoalbum_delete('json');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function with an album name.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoalbumDeleteWithAlbum()
	{
		$_REQUEST['album'] = 'album_name';
		api_fr_photoalbum_delete('json');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function with an album name.
	 * @return void
	 */
	public function testApiFrPhotoalbumDeleteWithValidAlbum()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoalbumUpdate()
	{
		api_fr_photoalbum_update('json');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function with an album name.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoalbumUpdateWithAlbum()
	{
		$_REQUEST['album'] = 'album_name';
		api_fr_photoalbum_update('json');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function with an album name.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoalbumUpdateWithAlbumAndNewAlbum()
	{
		$_REQUEST['album'] = 'album_name';
		$_REQUEST['album_new'] = 'album_name';
		api_fr_photoalbum_update('json');
	}

	/**
	 * Test the api_fr_photoalbum_update() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFrPhotoalbumUpdateWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photoalbum_update('json');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function with an album name.
	 * @return void
	 */
	public function testApiFrPhotoalbumUpdateWithValidAlbum()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}

	/**
	 * Test the api_fr_photos_list() function.
	 * @return void
	 */
	public function testApiFrPhotosList()
	{
		$result = api_fr_photos_list('json');
		$this->assertArrayHasKey('photo', $result);
	}

	/**
	 * Test the api_fr_photos_list() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFrPhotosListWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photos_list('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoCreateUpdate()
	{
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFrPhotoCreateUpdateWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function with an album name.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoCreateUpdateWithAlbum()
	{
		$_REQUEST['album'] = 'album_name';
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function with the update mode.
	 * @return void
	 */
	public function testApiFrPhotoCreateUpdateWithUpdate()
	{
		$this->markTestIncomplete('We need to create a dataset for this');
	}

	/**
	 * Test the api_fr_photo_create_update() function with an uploaded file.
	 * @return void
	 */
	public function testApiFrPhotoCreateUpdateWithFile()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_fr_photo_delete() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoDelete()
	{
		api_fr_photo_delete('json');
	}

	/**
	 * Test the api_fr_photo_delete() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFrPhotoDeleteWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photo_delete('json');
	}

	/**
	 * Test the api_fr_photo_delete() function with a photo ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoDeleteWithPhotoId()
	{
		$_REQUEST['photo_id'] = 1;
		api_fr_photo_delete('json');
	}

	/**
	 * Test the api_fr_photo_delete() function with a correct photo ID.
	 * @return void
	 */
	public function testApiFrPhotoDeleteWithCorrectPhotoId()
	{
		$this->markTestIncomplete('We need to create a dataset for this.');
	}

	/**
	 * Test the api_fr_photo_detail() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFrPhotoDetail()
	{
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiFrPhotoDetailWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function with a photo ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\NotFoundException
	 */
	public function testApiFrPhotoDetailWithPhotoId()
	{
		$_REQUEST['photo_id'] = 1;
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function with a correct photo ID.
	 * @return void
	 */
	public function testApiFrPhotoDetailCorrectPhotoId()
	{
		$this->markTestIncomplete('We need to create a dataset for this.');
	}

	/**
	 * Test the api_account_update_profile_image() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiAccountUpdateProfileImage()
	{
		api_account_update_profile_image('json');
	}

	/**
	 * Test the api_account_update_profile_image() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testApiAccountUpdateProfileImageWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_account_update_profile_image('json');
	}

	/**
	 * Test the api_account_update_profile_image() function with an uploaded file.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiAccountUpdateProfileImageWithUpload()
	{
		$this->markTestIncomplete();
	}


	/**
	 * Test the api_account_update_profile() function.
	 * @return void
	 */
	public function testApiAccountUpdateProfile()
	{
		$_POST['name'] = 'new_name';
		$_POST['description'] = 'new_description';
		$result = api_account_update_profile('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		$this->assertEquals($this->selfUser['id'], $result['user']['cid']);
		$this->assertEquals('DFRN', $result['user']['location']);
		$this->assertEquals($this->selfUser['nick'], $result['user']['screen_name']);
		$this->assertEquals('dfrn', $result['user']['network']);
		$this->assertEquals('new_name', $result['user']['name']);
		$this->assertEquals('new_description', $result['user']['description']);
	}

	/**
	 * Test the check_acl_input() function.
	 * @return void
	 */
	public function testCheckAclInput()
	{
		$result = check_acl_input('<aclstring>');
		// Where does this result come from?
		$this->assertEquals(1, $result);
	}

	/**
	 * Test the check_acl_input() function with an empty ACL string.
	 * @return void
	 */
	public function testCheckAclInputWithEmptyAclString()
	{
		$result = check_acl_input(' ');
		$this->assertFalse($result);
	}

	/**
	 * Test the save_media_to_database() function.
	 * @return void
	 */
	public function testSaveMediaToDatabase()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the post_photo_item() function.
	 * @return void
	 */
	public function testPostPhotoItem()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the prepare_photo_data() function.
	 * @return void
	 */
	public function testPreparePhotoData()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_remoteauth() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFriendicaRemoteauth()
	{
		api_friendica_remoteauth();
	}

	/**
	 * Test the api_friendica_remoteauth() function with an URL.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testApiFriendicaRemoteauthWithUrl()
	{
		$_GET['url'] = 'url';
		$_GET['c_url'] = 'url';
		api_friendica_remoteauth();
	}

	/**
	 * Test the api_friendica_remoteauth() function with a correct URL.
	 * @return void
	 */
	public function testApiFriendicaRemoteauthWithCorrectUrl()
	{
		$this->markTestIncomplete("We can't use an assertion here because of App->redirect().");
		$_GET['url'] = 'url';
		$_GET['c_url'] = $this->selfUser['nurl'];
		api_friendica_remoteauth();
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
