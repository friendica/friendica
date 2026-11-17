<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model\Post;

use Friendica\Database\DBA;
use Friendica\Model\Post\Question;
use Friendica\Model\Post\QuestionOption;
use Friendica\Model\Post\QuestionVote;
use Friendica\Protocol\ActivityPub\Processor;
use Friendica\Test\FixtureTestCase;

class QuestionTest extends FixtureTestCase
{
	private function createUriId(): int
	{
		DBA::insert('item-uri', ['uri' => 'https://mastodon.example/statuses/' . uniqid()]);
		return DBA::lastInsertId();
	}

	private function createPublicContact(): int
	{
		$url = 'https://friendica.local/profile/' . uniqid();
		DBA::insert('contact', ['uid' => 0, 'url' => $url, 'nurl' => $url]);
		return DBA::lastInsertId();
	}

	private function storeQuestion(int $uriId, array $names, bool $multiple = false, int $replies = 0): void
	{
		$options = array_map(fn ($name): array => ['name' => $name, 'replies' => $replies], $names);

		$method = new \ReflectionMethod(Processor::class, 'storeQuestion');
		$method->invoke(null, ['type' => 'as:Update', 'question' => ['multiple' => $multiple, 'options' => $options]], ['uri-id' => $uriId]);
	}

	private function optionNames(int $uriId): array
	{
		return array_column(QuestionOption::getByURIId($uriId), 'name');
	}

	public function testIsModifiedIsFalseForUnknownQuestions(): void
	{
		self::assertFalse(Question::isModified($this->createUriId(), false, ['Yes', 'No']));
	}

	public function testIsModifiedDetectsChanges(): void
	{
		$uriId = $this->createUriId();
		$this->storeQuestion($uriId, ['Yes', 'No']);

		self::assertFalse(Question::isModified($uriId, false, ['Yes', 'No']));
		self::assertTrue(Question::isModified($uriId, false, ['Yes', 'Maybe']));
		self::assertTrue(Question::isModified($uriId, false, ['No', 'Yes']));
		self::assertTrue(Question::isModified($uriId, false, ['Yes']));
		self::assertTrue(Question::isModified($uriId, false, ['Yes', 'No', 'Maybe']));
		self::assertTrue(Question::isModified($uriId, true, ['Yes', 'No']));
	}

	public function testIsModifiedComparesTruncatedNames(): void
	{
		$uriId = $this->createUriId();
		$long  = str_repeat('ä', 300);
		$this->storeQuestion($uriId, [$long, 'No']);

		self::assertFalse(Question::isModified($uriId, false, [$long, 'No']));
	}

	public function testDeleteFromRemovesTrailingOptions(): void
	{
		$uriId = $this->createUriId();
		$this->storeQuestion($uriId, ['A', 'B', 'C']);

		QuestionOption::deleteFrom($uriId, 1);

		self::assertSame(['A'], $this->optionNames($uriId));
	}

	public function testCountUpdateKeepsVotes(): void
	{
		$uriId = $this->createUriId();
		$voter = $this->createPublicContact();
		$this->storeQuestion($uriId, ['Tacos', 'Pizza', 'Sushi']);
		QuestionVote::add($uriId, 1, $voter);

		$this->storeQuestion($uriId, ['Tacos', 'Pizza', 'Sushi'], false, 5);

		self::assertTrue(QuestionVote::hasVoted($uriId, $voter));
	}

	public function testChangedOptionsResetVotesAndRemoveStaleOptions(): void
	{
		$uriId = $this->createUriId();
		$voter = $this->createPublicContact();
		$this->storeQuestion($uriId, ['Tacos', 'Pizza', 'Sushi']);
		QuestionVote::add($uriId, 1, $voter);

		$this->storeQuestion($uriId, ['Tacos', 'Sushi']);

		self::assertFalse(QuestionVote::hasVoted($uriId, $voter));
		self::assertSame(['Tacos', 'Sushi'], $this->optionNames($uriId));
	}

	public function testChangedChoiceTypeResetsVotes(): void
	{
		$uriId = $this->createUriId();
		$voter = $this->createPublicContact();
		$this->storeQuestion($uriId, ['Yes', 'No']);
		QuestionVote::add($uriId, 0, $voter);

		$this->storeQuestion($uriId, ['Yes', 'No'], true);

		self::assertFalse(QuestionVote::hasVoted($uriId, $voter));
	}
}
