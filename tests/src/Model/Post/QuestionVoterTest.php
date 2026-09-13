<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model\Post;

use Friendica\Database\DBA;
use Friendica\Model\Post\QuestionVoter;
use Friendica\Test\FixtureTestCase;

class QuestionVoterTest extends FixtureTestCase
{
	private function createUriId(): int
	{
		DBA::insert('item-uri', ['uri' => 'https://friendica.local/objects/' . uniqid()]);
		return DBA::lastInsertId();
	}

	private function createUid(): int
	{
		// DBA::insert() treats an empty $param array as a no-op, so at least one field is required.
		DBA::insert('user', ['username' => 'test-' . uniqid()]);
		return DBA::lastInsertId();
	}

	public function testHasNotVotedInitially(): void
	{
		$uriId = $this->createUriId();
		$uid   = $this->createUid();

		self::assertFalse(QuestionVoter::hasVoted($uriId, $uid));
	}

	public function testAddRecordsAVote(): void
	{
		$uriId = $this->createUriId();
		$uid   = $this->createUid();

		self::assertTrue(QuestionVoter::add($uriId, 1, $uid));
		self::assertTrue(QuestionVoter::hasVoted($uriId, $uid));
	}

	public function testAddIsIdempotentForTheSameOption(): void
	{
		$uriId = $this->createUriId();
		$uid   = $this->createUid();

		self::assertTrue(QuestionVoter::add($uriId, 1, $uid));
		// A second Create activity for the same option is a silently ignored duplicate:
		// add() returns false here (nothing new was inserted), same as any other
		// Database::INSERT_IGNORE caller in this codebase — not an error to the caller.
		self::assertFalse(QuestionVoter::add($uriId, 1, $uid));

		$votes = DBA::selectToArray('post-question-voter', ['id'], ['uri-id' => $uriId, 'uid' => $uid]);
		self::assertCount(1, $votes);
	}

	public function testAddAllowsMultipleOptionsForMultipleChoicePolls(): void
	{
		$uriId = $this->createUriId();
		$uid   = $this->createUid();

		self::assertTrue(QuestionVoter::add($uriId, 1, $uid));
		self::assertTrue(QuestionVoter::add($uriId, 2, $uid));

		$votes = DBA::selectToArray('post-question-voter', ['id'], ['uri-id' => $uriId, 'uid' => $uid]);
		self::assertCount(2, $votes);
	}

	public function testHasVotedIsScopedToTheGivenPoll(): void
	{
		$uriIdA = $this->createUriId();
		$uriIdB = $this->createUriId();
		$uid    = $this->createUid();

		QuestionVoter::add($uriIdA, 1, $uid);

		self::assertTrue(QuestionVoter::hasVoted($uriIdA, $uid));
		self::assertFalse(QuestionVoter::hasVoted($uriIdB, $uid));
	}
}
