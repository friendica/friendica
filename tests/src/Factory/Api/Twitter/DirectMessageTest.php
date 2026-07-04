<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Factory\Api\Twitter;

use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Test\ApiTestCase;
use Friendica\Test\FixtureTestCase;

class DirectMessageTest extends FixtureTestCase
{
	/**
	 * Test the api_format_messages() function.
	 *
	 * @return void
	 */
	public function testApiFormatMessages(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../Fixtures/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTestCase::SELF_USER['id'])
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
	public function testApiFormatMessagesWithHtmlText(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../Fixtures/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTestCase::SELF_USER['id'], 'html')
			->toArray();

		self::assertEquals('item_title', $directMessage['title']);
		self::assertEquals('<b>item_body</b>', $directMessage['text']);
	}

	/**
	 * Test the api_format_messages() function with plain text.
	 *
	 * @return void
	 */
	public function testApiFormatMessagesWithPlainText(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../Fixtures/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTestCase::SELF_USER['id'], 'plain')
			->toArray();

		self::assertEquals('item_title', $directMessage['title']);
		self::assertEquals('item_body', $directMessage['text']);
	}

	/**
	 * Test the api_format_messages() function with the getUserObjects GET parameter set to false.
	 *
	 */
	public function testApiFormatMessagesWithoutUserObjects(): void
	{
		self::markTestIncomplete('Needs processing of "getUserObjects" first');

		/*
		 $this->loadFixture(__DIR__ . '/../../../../Fixtures/mail/mail.fixture.php', DI::dba());
		$ids = DI::dba()->selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$directMessage = (new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()))
			->createFromMailId($id, ApiTestCase::SELF_USER['id'], 'plain', $$GETUSEROBJECTS$$)
			->toArray();

		self::assertTrue(!isset($directMessage['sender']));
		self::assertTrue(!isset($directMessage['recipient']));
		*/
	}
}
