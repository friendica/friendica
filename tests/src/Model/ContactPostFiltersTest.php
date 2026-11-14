<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Test\ApiTestCase;
use Friendica\Test\FixtureTestCase;

class ContactPostFiltersTest extends FixtureTestCase
{
	private const UID = ApiTestCase::SELF_USER['id'];

	// Public contact of the fixture that is the author of the threads 1 and 3
	private const AUTHOR = 43;

	public function testOwnPublicContactIsSelf(): void
	{
		self::assertTrue(Contact::isSelf(Contact::getPublicIdByUserId(self::UID), self::UID));
	}

	public function testOtherContactIsNotSelf(): void
	{
		self::assertFalse(Contact::isSelf(self::AUTHOR, self::UID));
	}

	public function testNothingIsSelfWithoutContactOrUser(): void
	{
		self::assertFalse(Contact::isSelf(0, self::UID));
		self::assertFalse(Contact::isSelf(self::AUTHOR, 0));
	}

	public function testUnknownContactHasNoPosts(): void
	{
		self::assertSame('', Contact::getActivitiesFromId(9999, self::UID));
		self::assertSame('', Contact::getResharesFromId(9999, self::UID));
		self::assertSame('', Contact::getQuotesFromId(9999, self::UID));
		self::assertSame('', Contact::getThreadsFromId(9999, self::UID, 0, 0, [], true));
	}

	public function testBlockedUserSeesNoPosts(): void
	{
		DI::dba()->insert('user-contact', ['cid' => self::AUTHOR, 'uid' => self::UID, 'is-blocked' => true]);

		$message = DI::l10n()->t('%s has blocked you', Contact::getById(self::AUTHOR, ['name'])['name']);

		self::assertStringContainsString($message, Contact::getActivitiesFromId(self::AUTHOR, self::UID));
		self::assertStringContainsString($message, Contact::getResharesFromId(self::AUTHOR, self::UID));
		self::assertStringContainsString($message, Contact::getQuotesFromId(self::AUTHOR, self::UID));
		self::assertStringContainsString($message, Contact::getThreadsFromId(self::AUTHOR, self::UID, 0, 0, [], true));
	}
}
