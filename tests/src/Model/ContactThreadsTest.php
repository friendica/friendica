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

class ContactThreadsTest extends FixtureTestCase
{
	private const UID = ApiTestCase::SELF_USER['id'];

	// Public contact of the fixture that is the author of the threads 1 and 3
	private const AUTHOR = 43;

	// Public contact of the fixture that is the author of the threads 6 and 7
	private const OTHER_AUTHOR = 45;

	protected function setUp(): void
	{
		parent::setUp();

		DI::dba()->update('post-thread-user', ['created' => '2026-10-01 10:00:00'], ['uri-id' => 1, 'uid' => 0]);
		DI::dba()->update('post-thread-user', ['created' => '2026-10-02 10:00:00'], ['uri-id' => 3, 'uid' => 0]);
		DI::dba()->update('post-thread-user', ['created' => '2026-10-03 10:00:00'], ['uri-id' => 6, 'uid' => 0]);
	}

	public function testAllThreadsAreNewerThanAnOldDate(): void
	{
		self::assertSame(2, Contact::countNewThreadsFromId(self::AUTHOR, self::UID, '2026-09-01 00:00:00'));
	}

	public function testOnlyThreadsAfterTheGivenDateAreCounted(): void
	{
		self::assertSame(1, Contact::countNewThreadsFromId(self::AUTHOR, self::UID, '2026-10-01 10:00:00'));
		self::assertSame(0, Contact::countNewThreadsFromId(self::AUTHOR, self::UID, '2026-10-02 10:00:00'));
	}

	public function testThreadsOfOtherContactsAreNotCounted(): void
	{
		self::assertSame(2, Contact::countNewThreadsFromId(self::AUTHOR, self::UID, '2026-09-01 00:00:00'));
		self::assertSame(1, Contact::countNewThreadsFromId(self::OTHER_AUTHOR, self::UID, '2026-09-01 00:00:00'));
	}

	public function testUnknownContactHasNoThreads(): void
	{
		self::assertSame(0, Contact::countNewThreadsFromId(9999, self::UID, '2026-09-01 00:00:00'));
	}

	public function testNothingIsCountedWhenTheContactHasBlockedTheUser(): void
	{
		DI::dba()->insert('user-contact', ['cid' => self::AUTHOR, 'uid' => self::UID, 'is-blocked' => true]);

		self::assertSame(0, Contact::countNewThreadsFromId(self::AUTHOR, self::UID, '2026-09-01 00:00:00'));
	}
}
