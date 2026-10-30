<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;

class QuestionVote
{
	/**
	 * Store the votes of a local user and queue their delivery to the author of the question
	 *
	 * @param integer $uri_id  Id of the item-uri table entry of the question
	 * @param integer $uid     Id of the voting user
	 * @param array   $choices Ids of the selected question options
	 * @throws HTTPException\NotFoundException            if the question isn't visible to the user
	 * @throws HTTPException\UnprocessableEntityException if the votes aren't valid
	 * @throws \Exception
	 */
	public static function vote(int $uri_id, int $uid, array $choices): void
	{
		$question  = Post::selectFirstForUser($uid, ['question-id', 'question-multiple', 'question-end-time'], ['uri-id' => $uri_id, 'uid' => [0, $uid]]);
		$author_id = Contact::getPublicIdByUserId($uid);
		if (empty($question['question-id']) || empty($author_id)) {
			throw new HTTPException\NotFoundException();
		}

		if (!empty($question['question-end-time']) && ($question['question-end-time'] > DBA::NULL_DATETIME) && ($question['question-end-time'] < DateTimeFormat::utcNow())) {
			throw new HTTPException\UnprocessableEntityException(DI::l10n()->t('The poll has already ended.'));
		}

		$choices = array_values(array_unique(array_map(intval(...), $choices)));
		if (empty($choices) || (!$question['question-multiple'] && count($choices) > 1)) {
			throw new HTTPException\UnprocessableEntityException(DI::l10n()->t('Invalid number of choices.'));
		}

		if (array_diff($choices, array_column(QuestionOption::getByURIId($uri_id), 'id'))) {
			throw new HTTPException\UnprocessableEntityException(DI::l10n()->t('Invalid choice.'));
		}

		if (self::hasVoted($uri_id, $author_id)) {
			throw new HTTPException\UnprocessableEntityException(DI::l10n()->t('You have already voted on this poll.'));
		}

		$added = [];
		foreach ($choices as $choice) {
			if (!self::add($uri_id, $choice, $author_id)) {
				foreach ($added as $id) {
					self::delete($uri_id, $id, $author_id);
				}
				throw new HTTPException\UnprocessableEntityException(DI::l10n()->t('You have already voted on this poll.'));
			}
			$added[] = $choice;
		}

		foreach ($choices as $choice) {
			Worker::add(Worker::PRIORITY_HIGH, 'SendPollVote', $uid, $uri_id, $choice);
		}
	}

	/**
	 * Store a vote for a question option
	 *
	 * @param integer $uri_id    Id of the item-uri table entry of the question
	 * @param integer $id        Id of the question option
	 * @param integer $author_id Public contact id (uid=0) of the voter
	 * @return bool false if the vote already existed
	 * @throws \Exception
	 */
	public static function add(int $uri_id, int $id, int $author_id): bool
	{
		if (empty($uri_id) || empty($author_id)) {
			throw new BadMethodCallException('Empty URI-Id or author-id');
		}

		return DBA::insert('post-question-vote', [
			'uri-id'    => $uri_id,
			'id'        => $id,
			'author-id' => $author_id,
			'created'   => DateTimeFormat::utcNow(),
		], Database::INSERT_IGNORE);
	}

	/**
	 * Remove a vote for a question option
	 *
	 * @param integer $uri_id    Id of the item-uri table entry of the question
	 * @param integer $id        Id of the question option
	 * @param integer $author_id Public contact id (uid=0) of the voter
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete(int $uri_id, int $id, int $author_id): bool
	{
		return DBA::delete('post-question-vote', ['uri-id' => $uri_id, 'id' => $id, 'author-id' => $author_id]);
	}

	/**
	 * Remove all votes of a question
	 *
	 * @param integer $uri_id Id of the item-uri table entry of the question
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteByURIId(int $uri_id): bool
	{
		return DBA::delete('post-question-vote', ['uri-id' => $uri_id]);
	}

	/**
	 * Check if the given contact voted for the question option
	 *
	 * @param integer $uri_id    Id of the item-uri table entry of the question
	 * @param integer $id        Id of the question option
	 * @param integer $author_id Public contact id (uid=0) of the voter
	 * @return bool
	 * @throws \Exception
	 */
	public static function exists(int $uri_id, int $id, int $author_id): bool
	{
		return DBA::exists('post-question-vote', ['uri-id' => $uri_id, 'id' => $id, 'author-id' => $author_id]);
	}

	/**
	 * Fetch the ids of the question options the given contact voted for
	 *
	 * @param integer $uri_id    Id of the item-uri table entry of the question
	 * @param integer $author_id Public contact id (uid=0) of the voter
	 * @return int[]
	 * @throws \Exception
	 */
	public static function getChoices(int $uri_id, int $author_id): array
	{
		$votes = DBA::selectToArray('post-question-vote', ['id'], ['uri-id' => $uri_id, 'author-id' => $author_id], ['order' => ['id']]);
		return array_map(intval(...), array_column($votes, 'id'));
	}

	/**
	 * Check if the given contact voted on the question
	 *
	 * @param integer $uri_id    Id of the item-uri table entry of the question
	 * @param integer $author_id Public contact id (uid=0) of the voter
	 * @return bool
	 * @throws \Exception
	 */
	public static function hasVoted(int $uri_id, int $author_id): bool
	{
		if (empty($uri_id) || empty($author_id)) {
			throw new BadMethodCallException('Empty URI-Id or author-id');
		}

		return DBA::exists('post-question-vote', ['uri-id' => $uri_id, 'author-id' => $author_id]);
	}
}
