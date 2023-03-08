<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Factory\Api\Twitter;

use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Test\FixtureTest;
use Friendica\Test\src\Module\Api\ApiTest;

class DirectMessageTest extends FixtureTest
{
	/**
	 * Test the api_format_messages() function.
	 *
	 * @return void
	 */
	public function testApiFormatMessages()
	{
		$this->loadFixture(__DIR__ . '/../../../../datasets/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTest::SELF_USER['id'])
			->toArray();

		self::assertEquals('item_title' . "\n" . 'item_body', $directMessage['text']);
		self::assertIsInt($directMessage['id']);
		self::assertIsInt($directMessage['recipient_id']);
		self::assertIsInt($directMessage['sender_id']);
		self::assertEquals('selfcontact', $directMessage['recipient_screen_name']);
		self::assertEquals('friendcontact', $directMessage['sender_screen_name']);
	}

	/**
	 * Test the api_format_messages() function with HTML.
	 *
	 * @return void
	 */
	public function testApiFormatMessagesWithHtmlText()
	{
		$this->loadFixture(__DIR__ . '/../../../../datasets/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTest::SELF_USER['id'], 'html')
			->toArray();

		self::assertEquals('item_title', $directMessage['title']);
		self::assertEquals('<strong>item_body</strong>', $directMessage['text']);
	}

	/**
	 * Test the api_format_messages() function with plain text.
	 *
	 * @return void
	 */
	public function testApiFormatMessagesWithPlainText()
	{
		$this->loadFixture(__DIR__ . '/../../../../datasets/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTest::SELF_USER['id'], 'plain')
			->toArray();

		self::assertEquals('item_title', $directMessage['title']);
		self::assertEquals('item_body', $directMessage['text']);
	}

	/**
	 * Test the api_format_messages() function with the getUserObjects GET parameter set to false.
	 *
	 * @return void
	 */
	public function testApiFormatMessagesWithoutUserObjects()
	{
		self::markTestIncomplete('Needs processing of "getUserObjects" first');

		/*
		 $this->loadFixture(__DIR__ . '/../../../../datasets/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTest::SELF_USER['id'], 'plain', $$GETUSEROBJECTS$$)
			->toArray();

		self::assertTrue(!isset($directMessage['sender']));
		self::assertTrue(!isset($directMessage['recipient']));
		*/
	}
}
