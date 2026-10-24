<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Database\DBA;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;

class QuestionVoter
{
	/**
	 * Records that a local user voted for a question option.
	 * Safe to call more than once for the same uri-id/id/uid: a repeat call is a no-op.
	 *
	 * @param integer $uri_id Id of the item-uri table entry of the poll
	 * @param integer $id     Id of the question option (post-question-option.id)
	 * @param integer $uid    Id of the local user who voted
	 * @return bool           False both on a real failure and on an already-recorded vote
	 *                        (Database::INSERT_IGNORE semantics) — use hasVoted() beforehand
	 *                        if the two cases need to be told apart.
	 * @throws \Exception
	 */
	public static function add(int $uri_id, int $id, int $uid): bool
	{
		if (empty($uri_id) || empty($uid)) {
			throw new BadMethodCallException('Empty URI-Id or user id');
		}

		return DBA::insert('post-question-voter', [
			'uri-id'  => $uri_id,
			'id'      => $id,
			'uid'     => $uid,
			'created' => DateTimeFormat::utcNow(),
		], Database::INSERT_IGNORE);
	}

	/**
	 * Whether the given user has already voted on the given poll, for any option.
	 *
	 * @param integer $uri_id Id of the item-uri table entry of the poll
	 * @param integer $uid    Id of the local user
	 * @return bool
	 * @throws \Exception
	 */
	public static function hasVoted(int $uri_id, int $uid): bool
	{
		if (empty($uri_id) || empty($uid)) {
			throw new BadMethodCallException('Empty URI-Id or user id');
		}

		return DBA::exists('post-question-voter', ['uri-id' => $uri_id, 'uid' => $uid]);
	}
}
