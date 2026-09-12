<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model\Post;

use Friendica\Database\DBA;
use Friendica\Model\Post\QuestionVote;
use Friendica\Test\FixtureTestCase;

class QuestionVoteTest extends FixtureTestCase
{
	private function createUriId(): int
	{
		DBA::insert('item-uri', ['uri' => 'https://friendica.local/objects/' . uniqid()]);
		return DBA::lastInsertId();
	}

	private function createPublicContact(string $baseurl = 'https://friendica.local'): int
	{
		$url = $baseurl . '/profile/' . uniqid();
		DBA::insert('contact', ['uid' => 0, 'url' => $url, 'nurl' => $url]);
		return DBA::lastInsertId();
	}

	public function testHasNotVotedInitially(): void
	{
		self::assertFalse(QuestionVote::hasVoted($this->createUriId(), $this->createPublicContact()));
	}

	public function testAddRecordsAVote(): void
	{
		$uriId    = $this->createUriId();
		$authorId = $this->createPublicContact();

		self::assertTrue(QuestionVote::add($uriId, 1, $authorId));
		self::assertTrue(QuestionVote::hasVoted($uriId, $authorId));
	}

	public function testAddIgnoresDuplicates(): void
	{
		$uriId    = $this->createUriId();
		$authorId = $this->createPublicContact();

		self::assertTrue(QuestionVote::add($uriId, 1, $authorId));
		self::assertFalse(QuestionVote::add($uriId, 1, $authorId));
		self::assertSame(1, DBA::count('post-question-vote', ['uri-id' => $uriId, 'author-id' => $authorId]));
	}

	public function testAddAllowsSeveralOptionsPerVoter(): void
	{
		$uriId    = $this->createUriId();
		$authorId = $this->createPublicContact();

		self::assertTrue(QuestionVote::add($uriId, 1, $authorId));
		self::assertTrue(QuestionVote::add($uriId, 2, $authorId));
		self::assertSame(2, DBA::count('post-question-vote', ['uri-id' => $uriId, 'author-id' => $authorId]));
	}

	public function testLocalAndRemoteVotersAreStoredSideBySide(): void
	{
		$uriId  = $this->createUriId();
		$local  = $this->createPublicContact('https://friendica.local');
		$remote = $this->createPublicContact('https://mastodon.example');

		self::assertTrue(QuestionVote::add($uriId, 0, $local));
		self::assertTrue(QuestionVote::add($uriId, 0, $remote));
		self::assertSame(2, DBA::count('post-question-vote', ['uri-id' => $uriId, 'id' => 0]));
	}

	public function testHasVotedIsScopedToTheQuestion(): void
	{
		$uriIdA   = $this->createUriId();
		$uriIdB   = $this->createUriId();
		$authorId = $this->createPublicContact();

		QuestionVote::add($uriIdA, 1, $authorId);

		self::assertTrue(QuestionVote::hasVoted($uriIdA, $authorId));
		self::assertFalse(QuestionVote::hasVoted($uriIdB, $authorId));
	}

	public function testDeleteRemovesOnlyTheGivenOption(): void
	{
		$uriId    = $this->createUriId();
		$authorId = $this->createPublicContact();

		QuestionVote::add($uriId, 1, $authorId);
		QuestionVote::add($uriId, 2, $authorId);
		QuestionVote::delete($uriId, 1, $authorId);

		self::assertSame(1, DBA::count('post-question-vote', ['uri-id' => $uriId, 'author-id' => $authorId]));
		self::assertTrue(QuestionVote::hasVoted($uriId, $authorId));
	}

	public function testDeleteByURIIdRemovesAllVotesOfTheQuestion(): void
	{
		$uriIdA   = $this->createUriId();
		$uriIdB   = $this->createUriId();
		$authorId = $this->createPublicContact();

		QuestionVote::add($uriIdA, 0, $authorId);
		QuestionVote::add($uriIdA, 1, $this->createPublicContact());
		QuestionVote::add($uriIdB, 0, $authorId);
		QuestionVote::deleteByURIId($uriIdA);

		self::assertSame(0, DBA::count('post-question-vote', ['uri-id' => $uriIdA]));
		self::assertTrue(QuestionVote::hasVoted($uriIdB, $authorId));
	}
}
