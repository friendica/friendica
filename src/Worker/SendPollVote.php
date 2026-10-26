<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub\Transmitter;

/**
 * Send the vote of a local user for a question option to the author of the question
 */
class SendPollVote
{
	/**
	 * @param integer $uid    Id of the voting user
	 * @param integer $uri_id Id of the item-uri table entry of the question
	 * @param integer $choice Id of the question option
	 */
	public static function execute(int $uid, int $uri_id, int $choice)
	{
		$author_id = Contact::getPublicIdByUserId($uid);

		// The vote is gone when the question had been modified in the meantime
		if (empty($author_id) || !Post\QuestionVote::exists($uri_id, $choice, $author_id)) {
			return;
		}

		$question = Post::selectFirst(['uri', 'author-link'], ['uri-id' => $uri_id]);
		$option   = Post\QuestionOption::getOption($uri_id, $choice);
		if (empty($question) || empty($option)) {
			return;
		}

		if (Transmitter::sendQuestionVote($question['uri'], $question['author-link'], $option['name'], $uid)) {
			return;
		}

		if (!Worker::defer()) {
			DI::logger()->notice('Vote could not be delivered and is removed', ['uid' => $uid, 'uri-id' => $uri_id, 'choice' => $choice]);
			Post\QuestionVote::delete($uri_id, $choice, $author_id);
		}
	}
}
