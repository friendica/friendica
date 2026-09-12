<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

class QuestionVote
{
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
