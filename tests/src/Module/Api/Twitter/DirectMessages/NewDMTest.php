<?php

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\NewDM;
use Friendica\Test\src\Module\Api\ApiTest;

class NewDMTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_new() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNew()
	{
		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new NewDM($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run();

		self::assertEmpty((string)$response->getBody());
	}

	/**
	 * Test the api_direct_messages_new() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_direct_messages_new('json');
		*/
	}

	/**
	 * Test the api_direct_messages_new() function with an user ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithUserId()
	{
		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new NewDM($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run([
				'text'    => 'message_text',
				'user_id' => 43
			]);

		$json = $this->toJson($response);

		self::assertEquals(-1, $json->error);
	}

	/**
	 * Test the api_direct_messages_new() function with a screen name.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithScreenName()
	{
		DI::app()->setLoggedInUserNickname('selfcontact');

		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new NewDM($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run([
				'text'    => 'message_text',
				'user_id' => 44
			]);

		$json = $this->toJson($response);

		self::assertStringContainsString('message_text', $json->text);
		self::assertEquals('selfcontact', $json->sender_screen_name);
		self::assertEquals(1, $json->friendica_seen);
	}

	/**
	 * Test the api_direct_messages_new() function with a title.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithTitle()
	{
		DI::app()->setLoggedInUserNickname('selfcontact');

		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new NewDM($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run([
				'text'    => 'message_text',
				'user_id' => 44,
				'title'   => 'message_title',
			]);

		$json = $this->toJson($response);

		self::assertStringContainsString('message_text', $json->text);
		self::assertStringContainsString('message_title', $json->text);
		self::assertEquals('selfcontact', $json->sender_screen_name);
		self::assertEquals(1, $json->friendica_seen);
	}

	/**
	 * Test the api_direct_messages_new() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithRss()
	{
		DI::app()->setLoggedInUserNickname('selfcontact');

		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new NewDM($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'rss']))
			->run([
				'text'    => 'message_text',
				'user_id' => 44,
				'title'   => 'message_title',
			]);

		self::assertXml((string)$response->getBody(), 'direct-messages');
	}
}
