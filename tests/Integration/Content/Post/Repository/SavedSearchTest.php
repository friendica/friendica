<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Content\Post\Repository;

use Friendica\Content\Post\Repository\SavedSearch;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;

/** Lists the saved searches of a user, based on api.fixture.php. */
class SavedSearchTest extends FixtureTestCase
{
	private const USER_ID = 42;

	private SavedSearch $savedSearch;

	protected function setUp(): void
	{
		parent::setUp();

		$this->savedSearch = DI::getDice()->create(SavedSearch::class);
	}

	public function testTermsAreOrderedAlphabetically(): void
	{
		self::assertTrue(DI::dba()->insert('search', ['uid' => self::USER_ID, 'term' => '#friendica']));
		self::assertTrue(DI::dba()->insert('search', ['uid' => self::USER_ID, 'term' => 'Zebra']));

		self::assertSame(['#friendica', 'Saved search', 'Zebra'], $this->savedSearch->selectTermsByUid(self::USER_ID));
	}

	public function testTermsOfOtherUsersAreIgnored(): void
	{
		self::assertSame([], $this->savedSearch->selectTermsByUid(self::USER_ID + 1));
	}
}
