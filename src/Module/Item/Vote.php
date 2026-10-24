<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\QuestionOption;
use Friendica\Model\Post\QuestionVoter;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub\Transmitter;
use Friendica\Util\DateTimeFormat;

/**
 * Records and transmits a vote for one option of a poll
 */
class Vote extends BaseModule
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		if (empty($this->parameters['id']) || !isset($this->parameters['option'])) {
			throw new HTTPException\BadRequestException();
		}

		$itemId   = (int) $this->parameters['id'];
		$optionId = (int) $this->parameters['option'];
		$uid      = DI::userSession()->getLocalUserId();

		// selectFirstForUser (not the plain post-view) so a blocked/hidden poll can't be voted on.
		$item = Post::selectFirstForUser($uid, ['uri', 'uri-id', 'author-link', 'post-type', 'question-multiple', 'question-end-time'], ['uid' => [0, $uid], 'id' => $itemId]);
		if (empty($item) || $item['post-type'] != Item::PT_POLL) {
			throw new HTTPException\BadRequestException();
		}

		if (!empty($item['question-end-time']) && $item['question-end-time'] != DBA::NULL_DATETIME && $item['question-end-time'] < DateTimeFormat::utcNow()) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('This poll has already closed.'));
		}

		// Single-choice: one vote total, for any option. Multiple-choice: one vote per
		// option, each independent — this check must NOT be poll-wide for multiple-choice,
		// since the client fires one concurrent request per checked option.
		if (!$item['question-multiple'] && QuestionVoter::hasVoted($item['uri-id'], $uid)) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('You have already voted on this poll.'));
		}

		$option = QuestionOption::getOption($item['uri-id'], $optionId);
		if (empty($option)) {
			throw new HTTPException\BadRequestException();
		}

		// Record first and use the atomic INSERT_IGNORE result to settle races (two
		// concurrent requests for the same option, e.g. two open tabs): only the request
		// that actually inserts the row proceeds to transmit. A hasVoted()-then-transmit
		// ordering would leave a gap where both requests pass the check before either commits.
		if (!QuestionVoter::add($item['uri-id'], $optionId, $uid)) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('You have already voted on this poll.'));
		}

		if (!Transmitter::sendQuestionVote($item['uri'], $item['author-link'], $option['name'], $uid)) {
			// Delivery failed after recording — undo it so the user isn't stuck in a false
			// "voted" state and can retry.
			DBA::delete('post-question-voter', ['uri-id' => $item['uri-id'], 'id' => $optionId, 'uid' => $uid]);
			throw new HTTPException\InternalServerErrorException(DI::l10n()->t('Your vote could not be delivered.'));
		}

		$this->earlyJsonExit([
			'status'  => 'ok',
			'item_id' => $itemId,
			'option'  => $optionId,
		]);
	}
}
